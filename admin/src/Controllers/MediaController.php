<?php
// admin/src/Controllers/MediaController.php - Enhanced Media Controller with Moderation

class MediaController
{
    private $db;
    private $user;
    private $mediaModel;
    private $userModel;

    public function __construct()
    {
        $this->mediaModel = new MediaModel();
        $this->userModel = new UserModel();
        $this->db = Database::getInstance();
        $this->user = AuthController::getCurrentUser();
        
        // Check admin authentication
        AuthController::requireAuth();
        $this->requireRole('super_admin');
    }

    public function index()
    {
        try {
            // Get filters from query parameters
            $page = (int)($_GET['page'] ?? 1);
            $perPage = min((int)($_GET['per_page'] ?? 20), 100);
            $type = $_GET['type'] ?? '';
            $status = $_GET['status'] ?? '';
            $search = $_GET['search'] ?? '';
            $uploader = $_GET['uploader'] ?? '';

            // Build API request parameters
            $params = [
                'page' => $page,
                'per_page' => $perPage
            ];

            if ($type) $params['type'] = $type;
            if ($status) $params['status'] = $status;
            if ($search) $params['search'] = $search;
            if ($uploader) $params['uploader'] = $uploader;

            $mediaData = [];
            $pagination = [];
            $stats = [];
            $moderationStats = [];

            // Check if we have API access
            if (!ApiClient::isAuthenticated()) {
                LayoutHelper::addFlashMessage('API access not available. Some features may be limited.', 'warning');
            }

            try {
                // Fetch media data from API
                $mediaResponse = ApiClient::get('/admin/media', $params);
                $mediaData = $mediaResponse['data'] ?? [];
                $pagination = $mediaResponse['meta']['pagination'] ?? [];

                // Get media statistics
                $statsResponse = ApiClient::get('/admin/media/statistics');
                $stats = $statsResponse['data'] ?? [];

                // Get moderation statistics
                $moderationResponse = ApiClient::get('/admin/media/moderation/statistics');
                $moderationStats = $moderationResponse['data'] ?? [];

            } catch (\Exception $e) {
                error_log("API Error in MediaController::index: " . $e->getMessage());
                
                // If API fails, try to get data from local database as fallback
                $mediaData = $this->getLocalMediaData($params);
                $stats = $this->getLocalStats();
                $moderationStats = $this->getLocalModerationStats();
                
                LayoutHelper::addFlashMessage('Using local data. API connection failed: ' . $e->getMessage(), 'warning');
            }

            // Set page data
            LayoutHelper::setPageData(
                'Media Management',
                'Manage and moderate media files on the platform',
                [
                    ['label' => 'Media']
                ]
            );
            
            // Render the media view
            LayoutHelper::render('media/index', [
                'title' => 'Media Library',
                'media_items' => $mediaData,
                'pagination' => $pagination,
                'stats' => $stats,
                'moderation_stats' => $moderationStats,
                'filters' => [
                    'type' => $type,
                    'status' => $status,
                    'search' => $search,
                    'uploader' => $uploader
                ],
                'current_page' => $page,
                'per_page' => $perPage,
                'has_api_access' => ApiClient::isAuthenticated(),
                'status_options' => [
                    '' => 'All Status',
                    'pending' => 'Pending Review',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'flagged' => 'Flagged'
                ],
                'type_options' => [
                    '' => 'All Types',
                    'image' => 'Images',
                    'video' => 'Videos',
                    'audio' => 'Audio',
                    'document' => 'Documents',
                    'other' => 'Other'
                ]
            ]);

        } catch (\Exception $e) {
            $this->handleError($e, 'Failed to load media library');
        }
    }

    public function moderation()
    {
        try {
            // Get filters specific to moderation
            $page = (int)($_GET['page'] ?? 1);
            $perPage = min((int)($_GET['per_page'] ?? 20), 100);
            $type = $_GET['type'] ?? '';
            $status = $_GET['status'] ?? 'flagged';
            $search = $_GET['search'] ?? '';

            $params = [
                'page' => $page,
                'per_page' => $perPage,
                'type' => $type,
                'status' => $status,
                'search' => $search
            ];

            $mediaData = [];
            $pagination = [];
            $stats = [];

            if (ApiClient::isAuthenticated()) {
                try {
                    // Get moderation queue data
                    $response = ApiClient::get('/admin/media/moderation', $params);
                    $mediaData = $response['data'] ?? [];
                    $pagination = $response['meta']['pagination'] ?? [];

                    // Get moderation statistics
                    $statsResponse = ApiClient::get('/admin/media/moderation/statistics');
                    $stats = $statsResponse['data'] ?? [];

                } catch (\Exception $e) {
                    error_log("API Error in MediaController::moderation: " . $e->getMessage());
                    LayoutHelper::addFlashMessage('Failed to load moderation data: ' . $e->getMessage(), 'error');
                }
            }

            LayoutHelper::setPageData(
                'Media Moderation',
                'Review and moderate flagged media content',
                ['media', 'moderation']
            );

            LayoutHelper::render('media/moderation', [
                'title' => 'Media Moderation Queue',
                'media_items' => $mediaData,
                'pagination' => $pagination,
                'stats' => $stats,
                'filters' => [
                    'type' => $type,
                    'status' => $status,
                    'search' => $search
                ],
                'current_page' => $page,
                'per_page' => $perPage,
                'has_api_access' => ApiClient::isAuthenticated()
            ]);

        } catch (\Exception $e) {
            $this->handleError($e, 'Failed to load moderation queue');
        }
    }

    public function show()
    {
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            $this->redirect('/admin/media', 'error', 'Invalid media ID');
            return;
        }

        try {
            $media = null;
            $uploader = null;
            $moderationHistory = [];
            $flags = [];

            if (ApiClient::isAuthenticated()) {
                try {
                    // Get media details from API with moderation info
                    $response = ApiClient::get("/admin/media/moderation/{$id}");
                    $mediaData = $response['data'] ?? null;
                    
                    if ($mediaData) {
                        $media = $mediaData;
                        $moderationHistory = $mediaData['moderation_history'] ?? [];
                        $flags = $mediaData['flag_history'] ?? [];

                        if ($media && isset($media['uploader_id'])) {
                            $uploader = $this->userModel->findById($media['uploader_id']);
                        }
                    }
                } catch (\Exception $e) {
                    error_log("API Error in MediaController::show: " . $e->getMessage());
                    // Fall back to basic media info
                    $response = ApiClient::get("/admin/media/{$id}");
                    $media = $response['data'] ?? null;

                    if ($media && isset($media['uploader_id'])) {
                        $uploader = $this->userModel->findById($media['uploader_id']);
                    }
                }
            } else {
                // No API access, use local data
                $media = $this->getLocalMediaById($id);
                if ($media && isset($media['uploader_id'])) {
                    $uploader = $this->userModel->findById($media['uploader_id']);
                }
            }

            if (!$media) {
                $this->redirect('/admin/media', 'error', 'Media file not found');
                return;
            }

            // Set page data
            LayoutHelper::setPageData(
                'Media Details',
                'View and moderate media file',
                [
                    ['label' => 'Media Details and Moderation']
                ]
            );
            
            // Render the upload view
            LayoutHelper::render('media/show', [
                'title' => 'Media Details - ' . ($media['original_filename'] ?? 'Unknown'),
                'media' => $media,
                'uploader' => $uploader,
                'moderation_history' => $moderationHistory,
                'flags' => $flags,
                'has_api_access' => ApiClient::isAuthenticated(),
                'moderation_actions' => [
                    'approve' => 'Approve',
                    'reject' => 'Reject',
                    'flag' => 'Flag for Review',
                    'hide' => 'Hide from Public',
                    'restore' => 'Restore'
                ]
            ]);

        } catch (\Exception $e) {
            $this->handleError($e, 'Failed to load media details');
        }
    }

    public function edit()
    {
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            $this->redirect('/media', 'error', 'Invalid media ID');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleEdit($id);
            return;
        }

        try {
            $media = null;

            if (ApiClient::isAuthenticated()) {
                try {
                    // Get media details from API
                    $response = ApiClient::get("/admin/media/{$id}");
                    $media = $response['data'] ?? null;
                } catch (\Exception $e) {
                    error_log("API Error in MediaController::edit: " . $e->getMessage());
                    // Fall back to local data
                    $media = $this->getLocalMediaById($id);
                }
            } else {
                $media = $this->getLocalMediaById($id);
            }

            if (!$media) {
                $this->redirect('/media', 'error', 'Media file not found');
                return;
            }

            // Set page data
            LayoutHelper::setPageData(
                'Edit Media File',
                'Manage media file',
                [
                    ['label' => 'Media Upload']
                ]
            );
            
            // Render the upload view
            LayoutHelper::render('media/edit', [
                'title' => 'Edit Media - ' . ($media['original_filename'] ?? 'Unknown'),
                'media' => $media,
                'has_api_access' => ApiClient::isAuthenticated()
            ]);

        } catch (\Exception $e) {
            $this->handleError($e, 'Failed to load media for editing');
        }
    }

    private function handleEdit(int $id): void
    {
        $meadiaId = $id;

        try {
            // Validate CSRF token
            if (!$this->validateCSRF()) {
                throw new \Exception('Invalid security token');
            }

            $updateData = [
                'alt_text' => $_POST['alt_text'] ?? '',
                'caption' => $_POST['caption'] ?? '',
                'is_public' => isset($_POST['is_public']) ? 1 : 0
            ];

            // Update via API
            $response = ApiClient::put("/admin/media/{$meadiaId}", $updateData);

            if ($response['success'] ?? false) {
                $this->jsonResponse([
                    'success' => true,
                    'message' =>'Media updated successfully'
                ]);
            } else {
                throw new \Exception($response['message'] ?? 'Failed to update media');
            }

        } catch (\Exception $e) {
           $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        try {
            // Validate CSRF token
            if (!$this->validateCSRF()) {
                throw new \Exception('Invalid security token');
            }

            $id = (int)($_POST['id'] ?? 0);
            
            if (!$id) {
                throw new \Exception('Invalid media ID');
            }

            // Delete via API
            $response = ApiClient::delete("/media/{$id}");

            if ($response['success'] ?? false) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Media deleted successfully'
                ]);
            } else {
                throw new \Exception($response['message'] ?? 'Failed to delete media');
            }

        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function moderate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        try {
            // Validate CSRF token
            if (!$this->validateCSRF()) {
                throw new \Exception('Invalid security token');
            }

            $id = (int)($_POST['id'] ?? 0);
            $action = $_POST['action'] ?? '';
            $reason = $_POST['reason'] ?? 'Admin reason';

            if (!$id || !in_array($action, ['approve', 'reject', 'flag', 'hide', 'restore'])) {
                throw new \Exception('Invalid parameters');
            }

            // Check API access
            if (!ApiClient::isAuthenticated()) {
                throw new \Exception('API access required for moderation actions');
            }

            // Perform moderation action via API
            $response = ApiClient::put("/admin/media/moderation/{$id}", [
                'action' => $action,
                'reason' => $reason
            ]);

            if ($response['success'] ?? false) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => ucfirst($action) . ' action completed successfully'
                ]);
            } else {
                throw new \Exception($response['message'] ?? 'Moderation action failed');
            }

        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function bulkModerate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        try {
            // Validate CSRF token
            if (!$this->validateCSRF()) {
                throw new \Exception('Invalid security token');
            }

            $ids = $_POST['media_ids'] ?? [];
            $action = $_POST['action'] ?? '';
            $reason = $_POST['reason'] ?? '';

            if (empty($ids) || !in_array($action, ['approve', 'reject', 'flag', 'hide', 'restore', 'delete'])) {
                throw new \Exception('Invalid parameters');
            }

            // Convert to array if it's a string
            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }

            // Check API access
            if (!ApiClient::isAuthenticated()) {
                throw new \Exception('API access required for bulk moderation');
            }

            // Perform bulk moderation via API
            $response = ApiClient::post('/admin/media/moderation/bulk', [
                'media_ids' => $ids,
                'action' => $action,
                'reason' => $reason
            ]);

            if ($response['success'] ?? false) {
                $processed = $response['data']['processed'] ?? 0;
                $total = $response['data']['total'] ?? count($ids);
                $errors = $response['data']['errors'] ?? [];

                $message = "Successfully processed {$processed} of {$total} items";
                if (!empty($errors)) {
                    $message .= " (" . count($errors) . " failed)";
                }

                $this->jsonResponse([
                    'success' => true,
                    'message' => $message,
                    'processed' => $processed,
                    'total' => $total,
                    'errors' => $errors
                ]);
            } else {
                throw new \Exception($response['message'] ?? 'Bulk moderation failed');
            }

        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function flag(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        try {
            // Validate CSRF token
            if (!$this->validateCSRF()) {
                throw new \Exception('Invalid security token');
            }

            $id = (int)($_POST['id'] ?? 0);
            $flagType = $_POST['flag_type'] ?? '';
            $reason = $_POST['reason'] ?? '';

            $validFlagTypes = ['inappropriate', 'copyright', 'spam', 'misleading', 'adult_content', 'violence', 'hate_speech', 'other'];

            if (!$id || !in_array($flagType, $validFlagTypes) || empty($reason)) {
                throw new \Exception('Invalid parameters');
            }

            // Flag via API
            $response = ApiClient::post("/admin/media/{$id}/flag", [
                'flag_type' => $flagType,
                'reason' => $reason
            ]);

            if ($response['success'] ?? false) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Media flagged successfully'
                ]);
            } else {
                throw new \Exception($response['message'] ?? 'Failed to flag media');
            }

        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function export(): void
    {
        try {
            // Check API access
            if (!ApiClient::isAuthenticated()) {
                throw new \Exception('API access required for export');
            }

            $format = $_GET['format'] ?? 'csv';
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo = $_GET['date_to'] ?? '';

            $params = ['format' => $format];
            if ($dateFrom) $params['date_from'] = $dateFrom;
            if ($dateTo) $params['date_to'] = $dateTo;

            $response = ApiClient::get('/admin/media/moderation/export', $params);

            if ($response['success'] ?? false) {
                $downloadUrl = $response['data']['download_url'] ?? '';
                $filename = $response['data']['filename'] ?? 'report.csv';

                if ($downloadUrl) {
                    // Redirect to download
                    header('Location: ' . $downloadUrl);
                    exit;
                } else {
                    throw new \Exception('Download URL not provided');
                }
            } else {
                throw new \Exception($response['message'] ?? 'Export failed');
            }

        } catch (\Exception $e) {
            $this->redirect('/admin/media/moderation', 'error', 'Export failed: ' . $e->getMessage());
        }
    }

    // Enhanced upload method with moderation workflow
    public function upload()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUploadWithModeration();
            return;
        }

        // Set page data
        LayoutHelper::setPageData(
            'Upload Media Files',
            'Upload and manage media files with automatic moderation',
            [
                ['label' => 'Media Upload']
            ]
        );
        
        // Render the upload view
        LayoutHelper::render('media/upload', [
            'title' => 'Media Upload',
            'has_api_access' => ApiClient::isAuthenticated(),
            'max_file_size' => ini_get('upload_max_filesize'),
            'allowed_types' => [
                'Images' => 'JPG, PNG, GIF, WebP',
                'Videos' => 'MP4, AVI, MOV',
                'Audio' => 'MP3, WAV, OGG',
                'Documents' => 'PDF, DOC, DOCX, TXT'
            ]
        ]);
    }

    private function handleUploadWithModeration()
    {
        try {
            // Check API access
            if (!ApiClient::isAuthenticated()) {
                throw new \Exception('API access required for file uploads');
            }

            // Validate CSRF token
            if (!$this->validateCSRF()) {
                throw new \Exception('Invalid security token');
            }

            // Check if files were uploaded
            if (empty($_FILES['files']['name'][0])) {
                throw new \Exception('No files selected for upload');
            }

            $uploadedFiles = [];
            $errors = [];

            // Process each uploaded file
            for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
                if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['files']['name'][$i],
                        'type' => $_FILES['files']['type'][$i],
                        'tmp_name' => $_FILES['files']['tmp_name'][$i],
                        'size' => $_FILES['files']['size'][$i],
                        'error' => $_FILES['files']['error'][$i]
                    ];

                    try {
                        // Validate file before upload
                        $this->validateFile($file);
                        
                        // Upload via API with moderation
                        $response = ApiClient::uploadFile('/admin/media/upload', $file, [
                            'alt_text' => $_POST['alt_text'] ?? '',
                            'caption' => $_POST['caption'] ?? '',
                            'is_public' => isset($_POST['is_public']) ? 1 : 0,
                            'auto_approve' => true // Admin uploads are auto-approved
                        ]);

                        if (isset($response['success']) && $response['success']) {
                            if (isset($response['data']) && is_array($response['data'])) {
                                $uploadedFiles = array_merge($uploadedFiles, $response['data']);
                            }
                        } else {
                            $errors[] = "Failed to upload {$file['name']}: " . ($response['message'] ?? 'Unknown error');
                        }

                    } catch (\Exception $e) {
                        $errors[] = "Failed to upload {$file['name']}: " . $e->getMessage();
                        error_log("Upload error for {$file['name']}: " . $e->getMessage());
                    }
                } else {
                    $errors[] = "Upload error for file: " . $_FILES['files']['name'][$i] . " (Error code: " . $_FILES['files']['error'][$i] . ")";
                }
            }

            // Prepare response
            $successCount = count($uploadedFiles);
            $errorCount = count($errors);

            if ($successCount > 0) {
                $message = "Successfully uploaded {$successCount} file(s)";
                if ($errorCount > 0) {
                    $message .= " ({$errorCount} failed)";
                }
                
                // Return JSON response for AJAX
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => $message,
                        'uploaded' => $uploadedFiles,
                        'errors' => $errors,
                        'auto_approved' => true
                    ]);
                } else {
                    $this->redirect(Router::url('media'), 'success', $message);
                }
            } else {
                $errorMessage = 'No files were uploaded. ' . implode(', ', $errors);
                
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => $errors
                    ], 400);
                } else {
                    $this->redirect(Router::url('media/upload'), 'error', $errorMessage);
                }
            }

        } catch (\Exception $e) {
            error_log("Upload handling error: " . $e->getMessage());
            
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            } else {
                $this->redirect(Router::url('media/upload'), 'error', $e->getMessage());
            }
        }
    }

    public function bulkAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        try {
            // Validate CSRF token
            if (!$this->validateCSRF()) {
                throw new \Exception('Invalid security token');
            }

            $ids = $_POST['ids'] ?? [];
            $action = $_POST['action'] ?? '';

            if (empty($ids) || !in_array($action, ['approve', 'reject', 'delete'])) {
                throw new \Exception('Invalid parameters');
            }

            $successCount = 0;
            $errors = [];

            foreach ($ids as $id) {
                try {
                    if ($action === 'delete') {
                        $response = ApiClient::delete("/media/{$id}");
                    } else {
                        $response = ApiClient::put("/admin/moderation/media/{$id}", ['action' => $action]);
                    }
                    
                    if ($response['success'] ?? false) {
                        $successCount++;
                    } else {
                        $errors[] = "Failed to {$action} media ID {$id}";
                    }

                } catch (\Exception $e) {
                    $errors[] = "Error processing media ID {$id}: " . $e->getMessage();
                }
            }

            $message = "Successfully processed {$successCount} items";
            if (!empty($errors)) {
                $message .= " (" . count($errors) . " failed)";
            }

            $this->jsonResponse([
                'success' => $successCount > 0,
                'message' => $message,
                'processed' => $successCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }


    public function download(): void
    {
        try {
            $id = (int)($_GET['id'] ?? 0);
            
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid media ID'], 400);
                return;
            }

            // Check API access first
            if (ApiClient::isAuthenticated()) {
                try {
                    // Get download URL from API
                    $response = ApiClient::get("/admin/media/{$id}/download-url");
                    
                    if ($response['success'] ?? false) {
                        $downloadUrl = $response['data']['download_url'] ?? '';

                        $filename = $response['data']["filename"];
                        $mime_type = $response['data']["mime_type"];
                        
                        if ($downloadUrl) {
                            // Get the token
                            $token = ApiClient::getAuthToken();

                            // Initialize a cURL session to make the API request
                            $ch = curl_init($downloadUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                'Authorization: Bearer ' . $token, // Send token in the header
                            ]);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects if any
                            curl_setopt($ch, CURLOPT_HEADER, false); // Exclude response headers

                            // Execute the request
                            $response = curl_exec($ch);
                            // var_dump($response);
                            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);

                            if ($httpCode == 200 && $response !== false) {
                                // Set headers to stream the file to the client
                                header('Content-Type: '.$mime_type); // Adjust MIME type if known
                                header('Content-Disposition: attachment; filename="'.$filename.'"'); // Set filename
                                header('Content-Length: ' . strlen($response));
                                echo $response; // Output the file content
                                exit;
                            } else {
                                // Handle error (e.g., API request failed)
                                http_response_code($httpCode);
                                $errorMessage = "Download failed with status code: $httpCode";
                                $this->redirect(Router::url('media'), 'error', $errorMessage);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    error_log("API download failed: " . $e->getMessage());
                    // Fall through to direct download
                }
            }

            // Fallback: Direct download from local storage
            $media = $this->getLocalMediaById($id);
            
            if (!$media) {
                http_response_code(404);
                exit('File not found');
            }

            $filePath = dirname(__DIR__, 4) . '/storage/uploads/' . $media['file_path'];
            
            if (!file_exists($filePath)) {
                http_response_code(404);
                exit('File not found on server');
            }

            // Serve file directly
            $this->serveFileDirectly($filePath, $media['original_filename'], $media['mime_type']);

        } catch (\Exception $e) {
            error_log("Admin download error: " . $e->getMessage());
            http_response_code(500);
            exit('Download failed');
        }
    }

    private function serveFileDirectly(string $filePath, string $filename, string $mimeType): void
    {
        // Clean any output
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $fileSize = filesize($filePath);
        
        // Set download headers
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Content-Length: ' . $fileSize);
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        // Set limits for large files
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        
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


    // Helper methods for local data fallback
    private function getLocalMediaData(array $params): array
    {
        try {
            if (method_exists($this->mediaModel, 'getAllWithModeration')) {
                return $this->mediaModel->getAllWithModeration($params);
            } elseif (method_exists($this->mediaModel, 'getAll')) {
                return $this->mediaModel->getAll($params);
            }
            return [];
        } catch (\Exception $e) {
            error_log("Local media data error: " . $e->getMessage());
            return [];
        }
    }

    private function getLocalModerationStats(): array
    {
        try {
            if (method_exists($this->mediaModel, 'getModerationStats')) {
                return $this->mediaModel->getModerationStats();
            }
            return [
                'pending_count' => 0,
                'flagged_count' => 0,
                'approved_count' => 0,
                'rejected_count' => 0
            ];
        } catch (\Exception $e) {
            error_log("Local moderation stats error: " . $e->getMessage());
            return [];
        }
    }

    private function getLocalStats(): array
    {
        try {
            if (method_exists($this->mediaModel, 'getStats')) {
                return $this->mediaModel->getStats();
            }
            return [];
        } catch (\Exception $e) {
            error_log("Local stats error: " . $e->getMessage());
            return [];
        }
    }

    private function getLocalMediaById(int $id): ?array
    {
        try {
            if (method_exists($this->mediaModel, 'findById')) {
                return $this->mediaModel->findById($id);
            }
            return null;
        } catch (\Exception $e) {
            error_log("Local media by ID error: " . $e->getMessage());
            return null;
        }
    }

    // Helper methods remain the same...
    private function requireRole(string $role): void
    {
        if (!$this->user || $this->user['role'] !== $role) {
            throw new \Exception('Insufficient permissions');
        }
    }

    private function redirect(string $url, string $type = '', string $message = ''): void
    {
        if ($message) {
            LayoutHelper::addFlashMessage($message, $type);
        }
        header('Location: ' . $url);
        exit;
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function validateFile(array $file): void
    {
        // Check file size
        $maxSize = $this->parseSize(ini_get('upload_max_filesize'));
        if ($file['size'] > $maxSize) {
            throw new \Exception("File {$file['name']} is too large. Maximum size: " . ini_get('upload_max_filesize'));
        }

        // Check file type
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/avi', 'video/quicktime',
            'audio/mpeg', 'audio/wav', 'audio/ogg',
            'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain', 'application/zip'
        ];

        if (!in_array($file['type'], $allowedTypes)) {
            throw new \Exception("File type {$file['type']} not allowed for {$file['name']}");
        }

        // Check if file actually exists
        if (!file_exists($file['tmp_name'])) {
            throw new \Exception("Uploaded file {$file['name']} not found");
        }
    }

    private function parseSize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size)-1]);
        $size = (int) $size;

        switch($last) {
            case 'g': $size *= 1024;
            case 'm': $size *= 1024;
            case 'k': $size *= 1024;
        }

        return $size;
    }

    private function validateCSRF(): bool
    {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        return validateCSRFToken($token);
    }

    private function handleError(\Exception $e, string $message = ''): void
    {
        error_log('MediaController Error: ' . $e->getMessage());
        $this->redirect('/admin/media', 'error', $message ?: $e->getMessage());
    }
}