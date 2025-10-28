<?php

// api/src/Controllers/Auth/PasswordController.php - Password Management

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\EmailService;
use App\Validators\UserValidator;
use App\Exceptions\ValidationException;

class PasswordController extends BaseController
{
    private User $userModel;
    private EmailService $emailService;
    private UserValidator $validator;

    public function __construct(User $userModel, EmailService $emailService, UserValidator $validator)
    {
        $this->userModel = $userModel;
        $this->emailService = $emailService;
        $this->validator = $validator;
        parent::__construct();
    }

    public function forgotPassword(Request $request): Response
    {
        $data = $request->getData();
        
        if (empty($data['email'])) {
            return $this->errorResponse('Email is required');
        }

        $user = $this->userModel->findByEmail($data['email']);
        if (!$user) {
            // Don't reveal if email exists
            return $this->successResponse([], 'If the email exists, a reset link has been sent');
        }

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        // Store reset token
        $sql = "INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (?, ?, ?, NOW())";
        $this->db->execute($sql, [$user['email'], $token, $expiresAt]);

        // Send reset email
        $this->emailService->sendPasswordResetEmail($user, $token);

        return $this->successResponse([], 'If the email exists, a reset link has been sent');
    }

    public function resetPassword(Request $request): Response
    {
        $data = $request->getData();
        
        if (empty($data['token']) || empty($data['password'])) {
            return $this->errorResponse('Token and new password are required');
        }

        // Validate password
        $validation = $this->validator->validatePasswordChange([
            'current_password' => 'dummy', // Skip current password check
            'new_password' => $data['password'],
            'new_password_confirmation' => $data['password_confirmation'] ?? ''
        ]);

        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        // Find valid reset token
        $sql = "SELECT * FROM password_resets 
                WHERE token = ? AND expires_at > NOW() AND used_at IS NULL";
        $reset = $this->db->fetch($sql, [$data['token']]);

        if (!$reset) {
            return $this->errorResponse('Invalid or expired reset token');
        }

        // Update password
        $user = $this->userModel->findByEmail($reset['email']);
        $success = $this->userModel->updatePassword($user['id'], $data['password']);

        if ($success) {
            // Mark token as used
            $sql = "UPDATE password_resets SET used_at = NOW() WHERE id = ?";
            $this->db->execute($sql, [$reset['id']]);

            // Log activity
            $this->logActivity($user['id'], 'password_reset', 'user', $user['id']);

            return $this->successResponse([], 'Password reset successfully');
        } else {
            return $this->errorResponse('Failed to reset password');
        }
    }

    public function changePassword(Request $request): Response
    {
        $userId = $request->user['id'];
        $data = $request->getData();
        
        $validation = $this->validator->validatePasswordChange($data);
        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        // Verify current password
        $user = $this->userModel->find($userId);
        if (!$this->userModel->verifyPassword($data['current_password'], $user['password_hash'])) {
            return $this->errorResponse('Current password is incorrect');
        }

        // Update password
        $success = $this->userModel->updatePassword($userId, $data['new_password']);

        if ($success) {
            // Log activity
            $this->logActivity($userId, 'password_changed', 'user', $userId);

            return $this->successResponse([], 'Password changed successfully');
        } else {
            return $this->errorResponse('Failed to change password');
        }
    }

    private function logActivity(int $userId, string $action, string $targetType, int $targetId): void
    {
        $sql = "INSERT INTO activity_logs (user_id, action, target_type, target_id, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $this->db->execute($sql, [
            $userId,
            $action,
            $targetType,
            $targetId,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
}