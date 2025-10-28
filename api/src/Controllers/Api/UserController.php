<?php
// api/src/Controllers/Api/UserController.php - User API Controller

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\EmailService;
use App\Validators\UserValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;

class UserController extends BaseController
{
    private User $userModel;
    private FileUploadService $fileUploadService;
    private EmailService $emailService;
    private UserValidator $validator;

    public function __construct(
        User $userModel, 
        FileUploadService $fileUploadService,
        EmailService $emailService,
        UserValidator $validator
    ) {
        $this->userModel = $userModel;
        $this->fileUploadService = $fileUploadService;
        $this->emailService = $emailService;
        $this->validator = $validator;
        parent::__construct();
    }

    public function profile(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            throw new NotFoundException('User not found');
        }

        return $this->response->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function publicProfile(Request $request, string $id): Response
    {
        $user = $this->userModel->find((int)$id);
        
        if (!$user || $user['status'] !== 'active') {
            throw new NotFoundException('User not found');
        }

        // Return only public information
        $publicData = [
            'id' => $user['id'],
            'uuid' => $user['uuid'],
            'username' => $user['username'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'profile_image' => $user['profile_image'],
            'bio' => $user['bio'],
            'location' => $user['location'],
            'website' => $user['website'],
            'social_twitter' => $user['social_twitter'],
            'social_facebook' => $user['social_facebook'],
            'social_linkedin' => $user['social_linkedin'],
            'is_verified_contributor' => $user['is_verified_contributor'],
            'created_at' => $user['created_at']
        ];

        // Get user statistics
        $stats = $this->getUserPublicStats((int)$id);

        return $this->response->json([
            'success' => true,
            'data' => [
                'user' => $publicData,
                'statistics' => $stats
            ]
        ]);
    }

    public function updateProfile(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        $data = $request->getData();
        
        // Validate input
        $validation = $this->validator->validateProfileUpdate($data, $userId);
        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        // Remove sensitive fields that shouldn't be updated via this endpoint
        unset($data['password'], $data['email'], $data['role'], $data['status']);

        $updatedUser = $this->userModel->update($userId, $data);
        
        if (!$updatedUser) {
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to update profile'
            ], 400);
        }

        // Log activity
        $this->userModel->logActivity($userId, 'profile_updated', 'user', $userId);

        return $this->response->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $updatedUser
        ]);
    }

    public function uploadAvatar(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        $files = $request->getFiles();
        
        if (!isset($files['avatar']) || $files['avatar']['error'] !== UPLOAD_ERR_OK) {
            return $this->response->json([
                'success' => false,
                'message' => 'No valid file uploaded'
            ], 400);
        }

        try {
            $uploadResult = $this->fileUploadService->upload($files['avatar'], $userId, 'avatars');
            
            // Update user profile with avatar path
            $this->userModel->update($userId, [
                'profile_image' => $uploadResult['url']
            ]);

            // Log activity
            $this->userModel->logActivity($userId, 'avatar_uploaded', 'media', $uploadResult['id']);

            return $this->response->json([
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'data' => $uploadResult
            ]);

        } catch (\Exception $e) {
            return $this->response->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get enhanced user bookmarks with category organization
     */
    public function bookmarks(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 20), 100);
        $category = $request->getQuery('category');
        $contentType = $request->getQuery('content_type');
        $search = $request->getQuery('search');

        $bookmarks = $this->userModel->getUserBookmarks($userId, $page, $perPage, [
            'category' => $category,
            'content_type' => $contentType,
            'search' => $search
        ]);

        return $this->response->json([
            'success' => true,
            'data' => $bookmarks['data'],
            'meta' => [
                'pagination' => $bookmarks['pagination']
            ]
        ]);
    }

    /**
     * Get bookmark statistics
     */
    public function bookmarkStats(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        $stats = $this->userModel->getBookmarkStatistics($userId);

        return $this->response->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get bookmarks organized by categories
     */
    public function bookmarksByCategory(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        $categorizedBookmarks = $this->userModel->getBookmarksByCategory($userId);

        return $this->response->json([
            'success' => true,
            'data' => $categorizedBookmarks
        ]);
    }

    /**
     * Bulk bookmark actions (delete, archive, etc.)
     */
    public function bulkBookmarkAction(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        $data = $request->getData();
        
        $bookmarkIds = $data['bookmark_ids'] ?? [];
        $action = $data['action'] ?? '';
        
        if (empty($bookmarkIds) || !in_array($action, ['delete', 'archive'])) {
            return $this->response->json([
                'success' => false,
                'message' => 'Invalid action or bookmark IDs'
            ], 400);
        }

        $result = $this->userModel->bulkBookmarkAction($userId, $bookmarkIds, $action);
        
        if ($result) {
            // Log activity
            $this->userModel->logActivity($userId, 'bulk_bookmark_' . $action, 'bookmark', count($bookmarkIds), [
                'action' => $action,
                'count' => count($bookmarkIds)
            ]);

            return $this->response->json([
                'success' => true,
                'message' => 'Bulk action completed successfully',
                'affected_rows' => $result
            ]);
        }

        return $this->response->json([
            'success' => false,
            'message' => 'Failed to perform bulk action'
        ], 500);
    }

    /**
     * Update bookmark category or notes
     */
    public function updateBookmark(Request $request, string $bookmarkId): Response
    {
        $userId = $request->getUser()['id'];
        $data = $request->getData();
        
        // Verify bookmark belongs to user
        $bookmark = $this->userModel->getUserBookmark($userId, (int)$bookmarkId);
        if (!$bookmark) {
            throw new NotFoundException('Bookmark not found');
        }

        $allowedFields = ['notes'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($updateData)) {
            return $this->response->json([
                'success' => false,
                'message' => 'No valid fields to update'
            ], 400);
        }

        $result = $this->userModel->updateBookmark((int)$bookmarkId, $updateData);
        
        if ($result) {
            return $this->response->json([
                'success' => true,
                'message' => 'Bookmark updated successfully'
            ]);
        }

        return $this->response->json([
            'success' => false,
            'message' => 'Failed to update bookmark'
        ], 500);
    }

    public function activity(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 20), 50);

        $activity = $this->userModel->getUserActivity($userId, $page, $perPage);

        return $this->response->json([
            'success' => true,
            'data' => $activity['data'],
            'meta' => [
                'pagination' => $activity['pagination']
            ]
        ]);
    }

    public function subscribe(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        $data = $request->getData();
        
        // $validation = $this->validator->validateSubscription($data);
        // if (!$validation['valid']) {
        //     throw new ValidationException('Validation failed', $validation['errors']);
        // }

        $subscriptionData = [
            'email' => $request->getUser()['email'],
            'user_id' => $userId,
            'subscription_type' => $data['type'],
            'category_id' => $data['category_id'] ?? null,
            'preferences' => json_encode($data['preferences'] ?? []),
            'status' => 'active'
        ];

        // Check if subscription already exists
        $existing = $this->userModel->verifySubscriptionExists();
        
        if ($existing) {
            return $this->response->json([
                'success' => false,
                'message' => 'Subscription already exists'
            ], 400);
        }

        $lastId = $this->userModel->createSubscription($subscriptionData);

        // Log activity
        $this->userModel->logActivity($userId, 'subscribed', 'subscription', $lastId);

        return $this->response->json([
            'success' => true,
            'message' => 'Subscription created successfully'
        ]);
    }

    public function updateSubscription(Request $request, string $id): Response
    {
        $userId = $request->getUser()['id'];
        $data = $request->getAllData();
        
        // Verify subscription belongs to user
        $subscription = $this->userModel->verifyUserIsSubscriber($userId);
        
        if (!$subscription) {
            throw new NotFoundException('Subscription not found');
        }

        $allowedFields = ['status', 'preferences'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (isset($updateData['preferences'])) {
            $updateData['preferences'] = json_encode($updateData['preferences']);
        }
        
        if (empty($updateData)) {
            return $this->response->json([
                'success' => false,
                'message' => 'No valid fields to update'
            ], 400);
        }

        $this->userModel->updateSubscription($updateData);

        return $this->response->json([
            'success' => true,
            'message' => 'Subscription updated successfully'
        ]);
    }

    public function unsubscribe(Request $request, string $id): Response
    {
        $userId = $request->getUser()['id'];

        $this->userModel->unsubscribeUser($userId);
        
        // Log activity
        $this->userModel->logActivity($userId, 'unsubscribed', 'subscription', (int)$id);

        return $this->response->json([
            'success' => true,
            'message' => 'Unsubscribed successfully'
        ]);
    }

    public function analyticsOverview(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        
        // Only contributors can view analytics
        if (!in_array($request->getUser()['role'], ['contributor', 'super_admin'])) {
            throw new AuthorizationException('Access denied');
        }

        $stats = [
            'news' => $this->userModel->getContentStats($userId, 'news'),
            'events' => $this->userModel->getContentStats($userId, 'events'),
            'total_views' => $this->userModel->getTotalViews($userId),
            'total_likes' => $this->userModel->getTotalLikes($userId),
            'total_comments' => $this->userModel->getTotalComments($userId),
            'recent_activity' => $this->userModel->getRecentAnalytics($userId)
        ];

        return $this->response->json([
            'success' => true,
            'data' => $stats
        ]);
    }

}
