<?php

// api/src/Controllers/Api/MediaController.php - Updated Media API Controller

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\Media;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\ModerationService;
use App\Services\NotificationService;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;

class MediaController extends BaseController
{
    private Media $mediaModel;
    private User $userModel;
    private FileUploadService $fileUploadService;
    private ModerationService $moderationService;
    private NotificationService $notificationService;

    public function __construct(
        Database $database,
        Media $mediaModel, 
        User $userModel, 
        ModerationService $moderationService, 
        NotificationService $notificationService, 
        FileUploadService $fileUploadService
    ) {
        parent::__construct();
        $this->db = $database;
        $this->mediaModel = $mediaModel;
        $this->userModel = $userModel;
        $this->fileUploadService = $fileUploadService;
        $this->moderationService = $moderationService;
        $this->notificationService = $notificationService;
    }

    public function upload(Request $request): Response
    {
        // Clean any output buffer to prevent JSON corruption
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        try {
            $files = $request->getFiles();
            
            if (empty($files)) {
                return $this->errorResponse('No files uploaded');
            }

            $userId = $request->getUser()['id'];
            $userRole = $request->getUser()['role'];
            $type = $request->getData('type') ?? 'general';
            $results = [];

            // Handle the files structure
            foreach ($files as $fieldName => $file) {
                if (isset($file['name']) && !is_array($file['name'])) {
                    // Single file
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        try {
                            $uploadResult = $this->fileUploadService->upload($file, $userId, $type);
                            
                            // Update with additional metadata if provided
                            $mediaId = $uploadResult['id'];
                            $updateData = [];
                            
                            if ($request->getData('alt_text')) {
                                $updateData['alt_text'] = $request->getData('alt_text');
                            }
                            
                            if ($request->getData('caption')) {
                                $updateData['caption'] = $request->getData('caption');
                            }
                            
                            // Get user for moderation
                            $user = $this->userModel->find($userId);
                            
                            // Auto-approve for super admins
                            if ($userRole === 'super_admin') {
                                $updateData['is_approved'] = 1;
                                $updateData['moderated_by'] = $userId;
                                $updateData['moderated_at'] = date('Y-m-d H:i:s');
                                $needsModeration = false;
                            } else {
                                // Check if needs moderation
                                $priority = $this->moderationService->calculateModerationPriority('media', $mediaId, $userId);
                                $needsModeration = !in_array($userRole, ['super_admin']) || 
                                                  in_array($priority, ['high', 'urgent']);
                                
                                if ($needsModeration) {
                                    // Add to unified moderation queue
                                    $this->moderationService->addToModerationQueue('media', $mediaId, $userId, $priority);
                                } else {
                                    // Auto-approve trusted users
                                    $updateData['is_approved'] = 1;
                                    $updateData['moderated_by'] = $userId;
                                    $updateData['moderated_at'] = date('Y-m-d H:i:s');
                                }
                            }
                            
                            if (!empty($updateData)) {
                                $this->mediaModel->update($mediaId, $updateData);
                                $uploadResult = array_merge($uploadResult, $updateData);
                            }
                            
                            // Update the result with moderation status
                            $uploadResult['needs_moderation'] = $needsModeration;
                            $uploadResult['moderation_status'] = $needsModeration ? 'pending' : 'approved';
                            
                            $results[] = $uploadResult;
                            
                        } catch (\Exception $e) {
                            error_log("Upload error for file {$file['name']}: " . $e->getMessage());
                            $results[] = [
                                'error' => $e->getMessage(),
                                'filename' => $file['name']
                            ];
                        }
                    } else {
                        $results[] = [
                            'error' => 'Upload error code: ' . $file['error'],
                            'filename' => $file['name'] ?? 'unknown'
                        ];
                    }
                } else {
                    // Handle multiple files (array structure)
                    if (is_array($file['name'])) {
                        for ($i = 0; $i < count($file['name']); $i++) {
                            $singleFile = [
                                'name' => $file['name'][$i],
                                'type' => $file['type'][$i],
                                'tmp_name' => $file['tmp_name'][$i],
                                'error' => $file['error'][$i],
                                'size' => $file['size'][$i]
                            ];
                            
                            if ($singleFile['error'] === UPLOAD_ERR_OK) {
                                try {
                                    $uploadResult = $this->fileUploadService->upload($singleFile, $userId, $type);
                                    
                                    // Update with metadata
                                    $mediaId = $uploadResult['id'];
                                    $updateData = [];
                                    
                                    if ($request->getData('alt_text')) {
                                        $updateData['alt_text'] = $request->getData('alt_text');
                                    }
                                    
                                    if ($request->getData('caption')) {
                                        $updateData['caption'] = $request->getData('caption');
                                    }
                                    
                                    // Auto-approve for super admins
                                    if ($userRole === 'super_admin') {
                                        $updateData['is_approved'] = 1;
                                        $updateData['moderated_by'] = $userId;
                                        $updateData['moderated_at'] = date('Y-m-d H:i:s');
                                        $needsModeration = false;
                                    } else {
                                        $priority = $this->moderationService->calculateModerationPriority('media', $mediaId, $userId);
                                        $needsModeration = !in_array($userRole, ['super_admin']) || 
                                                          in_array($priority, ['high', 'urgent']);
                                        
                                        if ($needsModeration) {
                                            $this->moderationService->addToModerationQueue('media', $mediaId, $userId, $priority);
                                        } else {
                                            $updateData['is_approved'] = 1;
                                            $updateData['moderated_by'] = $userId;
                                            $updateData['moderated_at'] = date('Y-m-d H:i:s');
                                        }
                                    }
                                    
                                    if (!empty($updateData)) {
                                        $this->mediaModel->update($mediaId, $updateData);
                                        $uploadResult = array_merge($uploadResult, $updateData);
                                    }
                                    
                                    $uploadResult['needs_moderation'] = $needsModeration;
                                    $uploadResult['moderation_status'] = $needsModeration ? 'pending' : 'approved';
                                    
                                    $results[] = $uploadResult;
                                    
                                } catch (\Exception $e) {
                                    error_log("Upload error for file {$singleFile['name']}: " . $e->getMessage());
                                    $results[] = [
                                        'error' => $e->getMessage(),
                                        'filename' => $singleFile['name']
                                    ];
                                }
                            } else {
                                $results[] = [
                                    'error' => 'Upload error code: ' . $singleFile['error'],
                                    'filename' => $singleFile['name'] ?? 'unknown'
                                ];
                            }
                        }
                    }
                }
            }

            // Log activity
            $successCount = count(array_filter($results, fn($r) => !isset($r['error'])));
            $moderationCount = count(array_filter($results, fn($r) => !isset($r['error']) && ($r['needs_moderation'] ?? false)));
            
            if ($successCount > 0) {
                $this->logActivity($userId, 'media_uploaded', 'media', 0, [
                    'files_uploaded' => $successCount,
                    'pending_moderation' => $moderationCount,
                    'type' => $type
                ]);
            }

            $message = "Uploaded {$successCount} of " . count($results) . " files";
            if ($moderationCount > 0) {
                $message .= ". {$moderationCount} files are pending moderation review.";
            }

            return $this->response->json([
                'success' => $successCount > 0,
                'message' => $message,
                'data' => $results,
                'moderation_summary' => [
                    'total_uploaded' => $successCount,
                    'pending_moderation' => $moderationCount,
                    'auto_approved' => $successCount - $moderationCount
                ]
            ]);

        } catch (\Exception $e) {
            error_log("API Upload Exception: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
            
            return $this->errorResponse('Upload failed: ' . $e->getMessage());
        }
    }

    public function index(Request $request): Response
    {
        try {
            $user = $request->getUser();
            // Only contributors and admins can view media library
            if (!isset($user) || !in_array($user['role'], ['contributor', 'super_admin'])) {
                throw new AuthorizationException('Contributor access required');
            }

            $page = (int)($request->getQuery('page') ?? 1);
            $perPage = min((int)($request->getQuery('per_page') ?? 20), 100);
            $type = $request->getQuery('type');
            $status = $request->getQuery('status');
            $search = $request->getQuery('search');
            $userId = $user['role'] === 'super_admin' ? null : $user['id'];

            // Build filters
            $filters = [];
            if ($type) $filters['type'] = $type;
            if ($status) $filters['status'] = $status;
            if ($search) $filters['search'] = $search;
            if ($userId) $filters['uploader_id'] = $userId;

            // Get media with moderation status from unified system
            $result = $this->mediaModel->getWithModerationStatus($page, $perPage, $filters);

            // Add moderation status labels and additional info for each item
            foreach ($result['data'] as &$media) {
                $media['moderation_status_label'] = $this->getModerationStatusLabel($media);
                $media['moderation_badge_class'] = $this->getModerationBadgeClass($media);
                $media['can_moderate'] = $user['role'] === 'super_admin';
                $media['needs_attention'] = $this->needsAttention($media);
            }

            return $this->paginatedResponse($result['data'], $result['pagination'], [
                'filters' => $filters,
                'available_statuses' => $this->getAvailableStatuses(),
                'summary' => $this->getModerationSummary($userId)
            ]);

        } catch (\Exception $e) {
            error_log("MediaController::index error: " . $e->getMessage());
            return $this->errorResponse('Failed to load media: ' . $e->getMessage());
        }
    }

    public function adminIndex(Request $request): Response
    {
        try {
            $user = $request->getUser();
            // Only admin can access full admin view
            if (!isset($user) || $user['role'] !== 'super_admin') {
                throw new AuthorizationException('Admin access required');
            }

            $page = (int)($request->getQuery('page') ?? 1);
            $perPage = min((int)($request->getQuery('per_page') ?? 50), 200);
            $type = $request->getQuery('type');
            $status = $request->getQuery('status');
            $search = $request->getQuery('search');
            $uploader = $request->getQuery('uploader');

            $filters = array_filter([
                'type' => $type,
                'status' => $status,
                'search' => $search,
                'uploader' => $uploader
            ]);

            // Get all media with full moderation details from unified system
            $result = $this->mediaModel->getAdminWithModerationQueue($page, $perPage, $filters);

            // Enhance each media item with additional moderation info
            foreach ($result['data'] as &$media) {
                $media['moderation_status_label'] = $this->getModerationStatusLabel($media);
                $media['moderation_badge_class'] = $this->getModerationBadgeClass($media);
                $media['priority_level'] = $this->getPriorityLevel($media);
                $media['days_pending'] = $this->getDaysPending($media);
                
                // Get uploader info if not already included
                if (!isset($media['uploader_name']) && isset($media['uploader_id'])) {
                    $uploader = $this->userModel->findById($media['uploader_id']);
                    $media['uploader_name'] = $uploader ? 
                        ($uploader['first_name'] . ' ' . $uploader['last_name']) : 
                        'Unknown';
                    $media['uploader_username'] = $uploader['username'] ?? 'unknown';
                }
            }

            return $this->paginatedResponse($result['data'], $result['pagination'], [
                'filters' => $filters,
                'available_statuses' => $this->getAvailableStatuses(),
                'moderation_summary' => $this->mediaModel->getFullModerationSummary(),
                'quick_actions' => $this->getQuickActions()
            ]);

        } catch (\Exception $e) {
            error_log("MediaController::adminIndex error: " . $e->getMessage());
            return $this->errorResponse('Failed to load admin media index: ' . $e->getMessage());
        }
    }

    public function show(Request $request, string $id): Response
    {
        $mediaId = (int)$id;
        $media = $this->mediaModel->find($mediaId);
        
        if (!$media) {
            throw new NotFoundException('Media not found');
        }

        // Check permissions
        if ($request->getUser()['role'] !== 'super_admin' && $media['uploader_id'] !== $request->getUser()['id']) {
            throw new AuthorizationException('Access denied');
        }

        return $this->successResponse($media);
    }

    public function update(Request $request, string $id): Response
    {
        $mediaId = (int)$id;
        $media = $this->mediaModel->find($mediaId);
        
        if (!$media) {
            throw new NotFoundException('Media not found');
        }

        // Check permissions
        if ($request->getUser()['role'] !== 'super_admin' && $media['uploader_id'] !== $request->getUser()['id']) {
            throw new AuthorizationException('Access denied');
        }

        $data = $request->getBodyObject();
        $allowedFields = ['alt_text', 'caption', 'is_public'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($updateData)) {
            return $this->errorResponse('No valid fields to update');
        }

        $updatedMedia = $this->mediaModel->update($mediaId, $updateData);
        
        if (!$updatedMedia) {
            return $this->errorResponse('Failed to update media');
        }

        // Log activity
        $this->logActivity($request->getUser()['id'], 'media_updated', 'media', $mediaId);

        return $this->successResponse($updatedMedia, 'Media updated successfully');
    }

    public function delete(Request $request, string $id): Response
    {
        $mediaId = (int)$id;
        $media = $this->mediaModel->find($mediaId);
        
        if (!$media) {
            throw new NotFoundException('Media not found');
        }

        // Check permissions
        if ($request->getUser()['role'] !== 'super_admin' && $media['uploader_id'] !== $request->getUser()['id']) {
            throw new AuthorizationException('Access denied');
        }

        try {
            $success = $this->fileUploadService->delete($mediaId, $request->getUser()['id']);
            
            if ($success) {
                // Log activity
                $this->logActivity($request->getUser()['id'], 'media_deleted', 'media', $mediaId, [
                    'filename' => $media['original_filename']
                ]);

                return $this->successResponse([], 'Media deleted successfully');
            } else {
                return $this->errorResponse('Failed to delete media');
            }
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function myUploads(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 20), 100);
        $type = $request->getQuery('type');

        if ($type) {
            $conditions = ['uploader_id' => $userId, 'file_type' => $type];
        } else {
            $conditions = ['uploader_id' => $userId];
        }

        $result = $this->mediaModel->paginate($page, $perPage, $conditions);

        return $this->paginatedResponse($result['data'], $result['pagination'], [
            'filters' => ['type' => $type]
        ]);
    }

    public function download(Request $request, string $id): Response
    {
        try {
            $mediaId = (int)$id;
            $media = $this->mediaModel->find($mediaId);
            
            if (!$media) {
                return $this->errorResponse('Media not found', [], 404);
            }

            // Check if media is public or user has access
            $user = $request->getUser();
            if (!$media['is_public'] && 
                (!$user || ($user['role'] !== 'super_admin' && $media['uploader_id'] !== $user['id']))) {
                return $this->errorResponse('Access denied', [], 403);
            }

            // Check if media is approved (unless user is admin or owner)
            if (!$media['is_approved'] && 
                (!$user || ($user['role'] !== 'super_admin' && $media['uploader_id'] !== $user['id']))) {
                return $this->errorResponse('Media not approved for download', [], 403);
            }

            // Construct file path
            $filePath = dirname(__DIR__, 4) . '/storage/uploads/media/' . $media['file_path'];
            
            if (!file_exists($filePath)) {
                error_log("File not found: " . $filePath);
                return $this->errorResponse('File not found on server', [], 404);
            }

            // Increment download count (non-blocking)
            try {
                $this->mediaModel->incrementDownloadCount($mediaId);
            } catch (\Exception $e) {
                error_log("Failed to increment download count: " . $e->getMessage());
            }

            // Log activity if user is logged in
            if ($user) {
                $this->logActivity($user['id'], 'media_downloaded', 'media', $mediaId, [
                    'filename' => $media['original_filename']
                ]);
            }

            // Serve the file
            $this->serveFile($filePath, $media['original_filename'], $media['mime_type'], $media['file_size']);
            
            return new Response();

        } catch (\Exception $e) {
            error_log("Media download error: " . $e->getMessage());
            return $this->errorResponse('Download failed: ' . $e->getMessage());
        }
    }

    public function serveMedia(Request $request, string $id): Response
    {
        // Same as download but for inline serving (images, videos)
        return $this->download($request, $id);
    }

    public function getDownloadUrl(Request $request, string $id): Response
    {
        try {
            $mediaId = (int)$id;
            $media = $this->mediaModel->find($mediaId);
            
            if (!$media) {
                return $this->errorResponse('Media not found', [], 404);
            }

            // Check permissions
            $user = $request->getUser();
            if (!$media['is_public'] && 
                (!$user || ($user['role'] !== 'super_admin' && $media['uploader_id'] !== $user['id']))) {
                return $this->errorResponse('Access denied', [], 403);
            }

            // Check if approved
            if (!$media['is_approved'] && 
                (!$user || ($user['role'] !== 'super_admin' && $media['uploader_id'] !== $user['id']))) {
                return $this->errorResponse('Media not approved', [], 403);
            }

            $baseUrl = $_ENV['APP_URL'] ?? 'http://10.30.252.49';
            $downloadUrl = $baseUrl . '/api/media/' . $mediaId . '/download';
            
            // Generate signed URL with expiration
            $expires = time() + 3600; // 1 hour
            $signature = hash_hmac('sha256', $mediaId . $expires, $_ENV['JWT_SECRET'] ?? 'secret');
            $signedUrl = $downloadUrl . '?expires=' . $expires . '&signature=' . $signature;

            return $this->successResponse([
                'download_url' => $downloadUrl,
                'signed_url' => $signedUrl,
                'expires_at' => date('Y-m-d H:i:s', $expires),
                'filename' => $media['original_filename'],
                'file_size' => $media['file_size'],
                'mime_type' => $media['mime_type'],
                'is_approved' => $media['is_approved']
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to generate download URL: ' . $e->getMessage());
        }
    }

    /**
     * Allow users to flag inappropriate media content
     */
    public function flagMedia(Request $request, string $id): Response
    {
        $mediaId = (int)$id;
        $data = $request->getData();
        
        $media = $this->mediaModel->find($mediaId);
        if (!$media) {
            throw new NotFoundException('Media not found');
        }

        // Validate flag data
        $flagType = $data['flag_type'] ?? '';
        $reason = trim($data['reason'] ?? '');
        
        $validFlagTypes = ['inappropriate', 'copyright', 'spam', 'misleading', 'adult_content', 'violence', 'hate_speech', 'other'];
        
        if (!in_array($flagType, $validFlagTypes)) {
            return $this->errorResponse('Invalid flag type. Must be one of: ' . implode(', ', $validFlagTypes));
        }
        
        if (empty($reason)) {
            return $this->errorResponse('Flag reason is required');
        }
        
        if (strlen($reason) < 10) {
            return $this->errorResponse('Flag reason must be at least 10 characters long');
        }

        try {
            // Use the unified moderation service to flag content
            $success = $this->moderationService->flagContent(
                'media', 
                $mediaId, 
                $request->getUser()['id'] ?? null, 
                $flagType, 
                $reason,
                $request->getUser()['email'] ?? null
            );
            
            if ($success) {
                // Log activity
                $this->logActivity($request->getUser()['id'] ?? 0, 'media_flagged', 'media', $mediaId, [
                    'flag_type' => $flagType,
                    'reason' => substr($reason, 0, 100)
                ]);
                
                return $this->successResponse([], 'Media flagged successfully. Our moderation team will review it.');
            } else {
                return $this->errorResponse('You have already flagged this media');
            }
            
        } catch (\Exception $e) {
            error_log("Media flagging error: " . $e->getMessage());
            return $this->errorResponse('Failed to flag media. Please try again.');
        }
    }

    /**
     * Get user's own media statistics (for contributors)
     */
    public function myStatistics(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        
        try {
            $stats = $this->mediaModel->getUserMediaStatistics($userId);
            return $this->successResponse($stats);
            
        } catch (\Exception $e) {
            error_log("Media statistics error: " . $e->getMessage());
            return $this->errorResponse('Failed to retrieve media statistics');
        }
    }

    public function statistics(Request $request): Response
    {
        // Only admin can view full statistics
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $stats = $this->mediaModel->getMediaStatistics();
        $recentUploads = $this->mediaModel->getRecentUploads(10);
        $orphanedMedia = $this->mediaModel->getOrphanedMedia();

        return $this->successResponse([
            'statistics' => $stats,
            'recent_uploads' => $recentUploads,
            'orphaned_count' => count($orphanedMedia)
        ]);
    }

    public function forceDelete(Request $request, string $id): Response
    {
        // Only admin can force delete
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $mediaId = (int)$id;
        $media = $this->mediaModel->find($mediaId);
        
        if (!$media) {
            throw new NotFoundException('Media not found');
        }

        try {
            $success = $this->fileUploadService->forceDelete($mediaId, $request->getUser()['id']);
            
            if ($success) {
                // Log activity
                $this->logActivity($request->getUser()['id'], 'media_force_deleted', 'media', $mediaId, [
                    'filename' => $media['original_filename']
                ]);

                return $this->successResponse([], 'Media permanently deleted');
            } else {
                return $this->errorResponse('Failed to delete media');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Delete failed: ' . $e->getMessage());
        }
    }

    public function cleanup(Request $request): Response
    {
        // Only admin can cleanup
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        try {
            $deletedCount = $this->fileUploadService->cleanupOrphanedFiles();
            
            // Log activity
            $this->logActivity($request->getUser()['id'], 'media_cleanup', 'system', 0, [
                'deleted_count' => $deletedCount
            ]);

            return $this->successResponse([
                'deleted_count' => $deletedCount
            ], "Cleaned up {$deletedCount} orphaned files");

        } catch (\Exception $e) {
            return $this->errorResponse('Cleanup failed: ' . $e->getMessage());
        }
    }

    // Private helper methods

    private function serveFile(string $filePath, string $filename, string $mimeType, int $fileSize): void
    {
        // Clean any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Sanitize filename
        $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Set headers
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . $safeFilename . '"');
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: public, max-age=3600');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
        
        // Set limits for large files
        set_time_limit(0);
        
        // Stream the file
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }
            fclose($handle);
        }
        
        exit;
    }

    private function getModerationStatusLabel(array $media): string
    {
        if ($media['is_flagged']) {
            return 'Flagged (' . ($media['flag_count'] ?? 0) . ')';
        } elseif ($media['is_rejected']) {
            return 'Rejected';
        } elseif ($media['is_approved']) {
            return 'Approved';
        } else {
            return 'Pending Review';
        }
    }

    private function getModerationBadgeClass(array $media): string
    {
        if ($media['is_flagged']) {
            return 'badge-danger';
        } elseif ($media['is_rejected']) {
            return 'badge-warning';
        } elseif ($media['is_approved']) {
            return 'badge-success';
        } else {
            return 'badge-info';
        }
    }

    private function needsAttention(array $media): bool
    {
        return $media['is_flagged'] || 
               ($media['flag_count'] > 0) || 
               (!$media['is_approved'] && !$media['is_rejected'] && $this->getDaysPending($media) > 3);
    }

    private function getPriorityLevel(array $media): string
    {
        if ($media['is_flagged'] && $media['flag_count'] >= 3) {
            return 'urgent';
        } elseif ($media['is_flagged']) {
            return 'high';
        } elseif (!$media['is_approved'] && !$media['is_rejected'] && $this->getDaysPending($media) > 7) {
            return 'high';
        } elseif (!$media['is_approved'] && !$media['is_rejected']) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    private function getDaysPending(array $media): int
    {
        if ($media['is_approved'] || $media['is_rejected']) {
            return 0;
        }
        
        $createdAt = new \DateTime($media['created_at']);
        $now = new \DateTime();
        return $now->diff($createdAt)->days;
    }

    private function getAvailableStatuses(): array
    {
        return [
            '' => 'All Status',
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'flagged' => 'Flagged'
        ];
    }

    private function getQuickActions(): array
    {
        return [
            'approve_selected' => 'Approve Selected',
            'reject_selected' => 'Reject Selected',
            'flag_selected' => 'Flag Selected',
            'hide_selected' => 'Hide Selected',
            'delete_selected' => 'Delete Selected'
        ];
    }

    private function getModerationSummary(?int $userId = null): array
    {
        return $this->mediaModel->getModerationSummary($userId);
    }

    private function logActivity(int $userId, string $action, string $targetType, int $targetId, array $metadata = []): void
    {
        try {
            if ($this->db === null) {
                error_log("Database not available for activity logging");
                return;
            }

            $sql = "INSERT INTO activity_logs (user_id, action, target_type, target_id, ip_address, user_agent, metadata, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $this->db->execute($sql, [
                $userId,
                $action,
                $targetType,
                $targetId,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                json_encode($metadata)
            ]);
        } catch (\Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}