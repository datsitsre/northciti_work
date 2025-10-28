<?php

// api/src/Controllers/Admin/UserManagementController.php - Admin User Management Controller

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\EmailService;
use App\Services\CacheService;
use App\Validators\UserValidator;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;

class UserManagementController extends BaseController
{
    private User $userModel;
    private EmailService $emailService;
    private CacheService $cache;
    private UserValidator $validator;

    public function __construct(
        User $userModel,
        EmailService $emailService,
        CacheService $cache,
        UserValidator $validator
    ) {
        $this->userModel = $userModel;
        $this->emailService = $emailService;
        $this->cache = $cache;
        $this->validator = $validator;
        parent::__construct();
    }

    public function index(Request $request): Response
    {
        // Only admin can access user management
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 20), 100);
        $role = $request->getQuery('role');
        $status = $request->getQuery('status');
        $search = $request->getQuery('search');

        if ($search) {
            $result = $this->userModel->searchUsers($search, $page, $perPage);
        } elseif ($role) {
            $result = $this->userModel->getByRole($role, $page, $perPage);
        } else {
            $conditions = [];
            if ($status) {
                $conditions['status'] = $status;
            }
            $result = $this->userModel->paginate($page, $perPage, $conditions);
        }

        return $this->paginatedResponse($result['data'], $result['pagination'], [
            'filters' => [
                'role' => $role,
                'status' => $status,
                'search' => $search
            ]
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        // Only admin can view user details
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $userId = (int)$id;
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            throw new NotFoundException('User not found');
        }

        // Get additional user information
        $userStats = $this->userModel->getUserStatistics($userId);
        $recentActivity = $this->userModel->getUserRecentActivity($userId, 10);
        $subscriptions = $this->userModel->getUserSubscriptions($userId);

        $data = array_merge($user, [
            'statistics' => $userStats,
            'recent_activity' => $recentActivity,
            'subscriptions' => $subscriptions
        ]);

        return $this->successResponse($data);
    }

    public function update(Request $request, string $id): Response
    {
        // Only admin can update users
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $userId = (int)$id;
        $data = $request->getData();
        
        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new NotFoundException('User not found');
        }

        // Validate input
        $validation = $this->validator->validateProfileUpdate($data, $userId);
        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        // Remove sensitive fields that require special handling
        unset($data['password'], $data['role'], $data['status']);

        $updatedUser = $this->userModel->update($userId, $data);
        
        if (!$updatedUser) {
            return $this->errorResponse('Failed to update user');
        }

        // Log activity
        $this->userModel->logActivity($request->getUser()['id'], 'user_updated', 'user', $userId, [
            'updated_by_admin' => true
        ]);

        return $this->successResponse($updatedUser, 'User updated successfully');
    }

    public function updateStatus(Request $request, string $id): Response
    {
        // Only admin can update user status
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $userId = (int)$id;
        $data = $request->getData();
        
        if (empty($data['status'])) {
            return $this->errorResponse('Status is required');
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new NotFoundException('User not found');
        }

        // Prevent admin from changing their own status
        if ($userId === $request->getUser()['id']) {
            return $this->errorResponse('Cannot change your own status');
        }

        $success = $this->userModel->updateUserStatus($userId, $data['status']);
        
        if (!$success) {
            return $this->errorResponse('Failed to update user status');
        }

        // Send notification email based on status change
        $this->sendStatusChangeNotification($user, $data['status'], $data['reason'] ?? '');

        // Log activity
        $this->userModel->logActivity($request->getUser()['id'], 'user_status_changed', 'user', $userId, [
            'old_status' => $user['status'],
            'new_status' => $data['status'],
            'reason' => $data['reason'] ?? ''
        ]);

        return $this->successResponse([], 'User status updated successfully');
    }

    public function updateRole(Request $request, string $id): Response
    {
        // Only admin can update user roles
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $userId = (int)$id;
        $data = $request->getData();
        
        if (empty($data['role'])) {
            return $this->errorResponse('Role is required');
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new NotFoundException('User not found');
        }

        // Prevent admin from changing their own role
        if ($userId === $request->getUser()['id']) {
            return $this->errorResponse('Cannot change your own role');
        }

        $success = $this->userModel->updateUserRole($userId, $data['role']);
        
        if (!$success) {
            return $this->errorResponse('Failed to update user role');
        }

        // Send notification email
        $this->sendRoleChangeNotification($user, $data['role']);

        // Log activity
        $this->userModel->logActivity($request->getUser()['id'], 'user_role_changed', 'user', $userId, [
            'old_role' => $user['role'],
            'new_role' => $data['role']
        ]);

        return $this->successResponse([], 'User role updated successfully');
    }

    public function delete(Request $request, string $id): Response
    {
        // Only admin can delete users
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $userId = (int)$id;
        
        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new NotFoundException('User not found');
        }

        // Prevent admin from deleting themselves
        if ($userId === $request->getUser()['id']) {
            return $this->errorResponse('Cannot delete your own account');
        }

        // Check if user has content that needs to be handled
        $contentCheck = $this->userModel->checkUserContent($userId);
        if ($contentCheck['has_content']) {
            return $this->errorResponse(
                'User has associated content that must be handled first',
                $contentCheck
            );
        }

        $deleted = $this->userModel->delete($userId);
        
        if (!$deleted) {
            return $this->errorResponse('Failed to delete user');
        }

        // Send notification email
        $this->sendAccountDeletionNotification($user);

        // Log activity
        $this->userModel->logActivity($request->getUser()['id'], 'user_deleted', 'user', $userId, [
            'deleted_user_email' => $user['email']
        ]);

        return $this->successResponse([], 'User deleted successfully');
    }

    public function sendEmail(Request $request, string $id): Response
    {
        // Only admin can send emails to users
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $userId = (int)$id;
        $data = $request->getData();
        
        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new NotFoundException('User not found');
        }

        // Validate email data
        $validation = $this->validateEmailData($data);
        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        $emailData = [
            'to_email' => $user['email'],
            'to_name' => $user['first_name'] . ' ' . $user['last_name'],
            'subject' => $data['subject'],
            'body_html' => $this->formatAdminEmail($data['message'], $user),
            'priority' => $data['priority'] ?? 'normal'
        ];

        try {
            $success = $this->emailService->queue($emailData);
            
            if ($success) {
                // Log activity
                $this->userModel->logActivity($request->getUser()['id'], 'email_sent', 'user', $userId, [
                    'subject' => $data['subject']
                ]);

                return $this->successResponse([], 'Email queued successfully');
            } else {
                return $this->errorResponse('Failed to queue email');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Email sending failed: ' . $e->getMessage());
        }
    }

    public function statistics(Request $request): Response
    {
        // Only admin can view user statistics
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $cacheKey = $this->cache->generateKey('admin_user_stats');
        
        $stats = $this->cache->remember($cacheKey, function() {
            return $this->userModel->getStatistics();
        }, 300); // Cache for 5 minutes

        // Get additional statistics
        $additionalStats = [
            'recent_registrations' => $this->userModel->getRecentRegistrations(7),
            'role_distribution' => $this->userModel->getRoleDistribution(),
            'status_distribution' => $this->userModel->getStatusDistribution(),
            'activity_stats' => $this->userModel->getActivityStatistics()
        ];

        return $this->successResponse(array_merge($stats, $additionalStats));
    }

    public function export(Request $request): Response
    {
        // Only admin can export user data
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $format = $request->getQuery('format', 'csv');
        $filters = [
            'role' => $request->getQuery('role'),
            'status' => $request->getQuery('status')
        ];

        try {
            $filename = $this->userModel->exportUsers($format, $filters);
            
            // Log activity
            $this->userModel->logActivity($request->getUser()['id'], 'users_exported', 'system', 0, [
                'format' => $format,
                'filters' => $filters
            ]);

            return $this->successResponse([
                'download_url' => '/api/admin/users/download/' . $filename,
                'filename' => $filename
            ], 'Export completed successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Export failed: ' . $e->getMessage());
        }
    }

    private function sendStatusChangeNotification(array $user, string $newStatus, string $reason = ''): void
    {
        $statusMessages = [
            'active' => 'Your account has been activated and you can now access all features.',
            'suspended' => 'Your account has been temporarily suspended.',
            'banned' => 'Your account has been permanently banned.',
            'pending' => 'Your account is pending review.'
        ];

        $message = $statusMessages[$newStatus] ?? 'Your account status has been updated.';
        if ($reason) {
            $message .= "\n\nReason: " . $reason;
        }

        $emailData = [
            'to_email' => $user['email'],
            'to_name' => $user['first_name'] . ' ' . $user['last_name'],
            'subject' => 'Account Status Update',
            'body_html' => $this->getStatusChangeEmailTemplate($user, $newStatus, $message),
            'priority' => 'high'
        ];

        $this->emailService->queue($emailData);
    }

    private function sendRoleChangeNotification(array $user, string $newRole): void
    {
        $roleMessages = [
            'contributor' => 'You have been granted contributor access and can now create and manage content.',
            'super_admin' => 'You have been granted administrator access.',
            'public' => 'Your role has been changed to public user.'
        ];

        $message = $roleMessages[$newRole] ?? 'Your role has been updated.';

        $emailData = [
            'to_email' => $user['email'],
            'to_name' => $user['first_name'] . ' ' . $user['last_name'],
            'subject' => 'Role Update Notification',
            'body_html' => $this->getRoleChangeEmailTemplate($user, $newRole, $message),
            'priority' => 'high'
        ];

        $this->emailService->queue($emailData);
    }

    private function sendAccountDeletionNotification(array $user): void
    {
        $emailData = [
            'to_email' => $user['email'],
            'to_name' => $user['first_name'] . ' ' . $user['last_name'],
            'subject' => 'Account Deletion Notice',
            'body_html' => $this->getAccountDeletionEmailTemplate($user),
            'priority' => 'high'
        ];

        $this->emailService->queue($emailData);
    }

    private function validateEmailData(array $data): array
    {
        $errors = [];

        if (empty($data['subject'])) {
            $errors['subject'] = 'Subject is required';
        } elseif (strlen($data['subject']) > 255) {
            $errors['subject'] = 'Subject must not exceed 255 characters';
        }

        if (empty($data['message'])) {
            $errors['message'] = 'Message is required';
        }

        if (isset($data['priority']) && !in_array($data['priority'], ['low', 'normal', 'high'])) {
            $errors['priority'] = 'Invalid priority level';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    private function formatAdminEmail(string $message, array $user): string
    {
        return "
        <html>
        <body>
            <h2>Message from " . ($_ENV['APP_NAME'] ?? 'News Platform') . " Administration</h2>
            <p>Hello {$user['first_name']},</p>
            " . nl2br(htmlspecialchars($message)) . "
            <hr>
            <p><small>This email was sent by the platform administration. Please do not reply to this email.</small></p>
        </body>
        </html>";
    }

    private function getStatusChangeEmailTemplate(array $user, string $status, string $message): string
    {
        return "
        <html>
        <body>
            <h2>Account Status Update</h2>
            <p>Hello {$user['first_name']},</p>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
            <p><strong>New Status:</strong> " . ucfirst($status) . "</p>
            <p>If you have any questions, please contact our support team.</p>
            <p>Best regards,<br>The " . ($_ENV['APP_NAME'] ?? 'News Platform') . " Team</p>
        </body>
        </html>";
    }

    private function getRoleChangeEmailTemplate(array $user, string $role, string $message): string
    {
        return "
        <html>
        <body>
            <h2>Role Update Notification</h2>
            <p>Hello {$user['first_name']},</p>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
            <p><strong>New Role:</strong> " . ucfirst(str_replace('_', ' ', $role)) . "</p>
            <p>You can now log in and access your new privileges.</p>
            <p>Best regards,<br>The " . ($_ENV['APP_NAME'] ?? 'News Platform') . " Team</p>
        </body>
        </html>";
    }

    private function getAccountDeletionEmailTemplate(array $user): string
    {
        return "
        <html>
        <body>
            <h2>Account Deletion Notice</h2>
            <p>Hello {$user['first_name']},</p>
            <p>This is to inform you that your account has been deleted from our platform.</p>
            <p>If you believe this was done in error, please contact our support team immediately.</p>
            <p>Thank you for being part of our community.</p>
            <p>Best regards,<br>The " . ($_ENV['APP_NAME'] ?? 'News Platform') . " Team</p>
        </body>
        </html>";
    }

    private function exportToCsv(array $users, string $filepath): void
    {
        $file = fopen($filepath, 'w');
        
        // Write header
        $headers = ['ID', 'UUID', 'Username', 'Email', 'First Name', 'Last Name', 'Role', 'Status', 'Email Verified', 'Created At', 'Last Login'];
        fputcsv($file, $headers);
        
        // Write data
        foreach ($users as $user) {
            fputcsv($file, [
                $user['id'],
                $user['uuid'],
                $user['username'],
                $user['email'],
                $user['first_name'],
                $user['last_name'],
                $user['role'],
                $user['status'],
                $user['email_verified'] ? 'Yes' : 'No',
                $user['created_at'],
                $user['last_login_at']
            ]);
        }
        
        fclose($file);
    }

}