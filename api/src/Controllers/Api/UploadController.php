<?php

// api/src/Controllers/Api/UploadController.php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Models\Media;

class UploadController extends BaseController
{
    private Media $mediaModel;

    public function __construct(Media $mediaModel)
    {
        $this->mediaModel = $mediaModel;
        parent::__construct();
    }

    private array $allowedImageTypes = [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/tiff',
        'image/avif',
        'image/webp'
    ];
    
    private int $maxFileSize = 1 * 1024 * 1024; // 1MB

    public function uploadImage(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            return $this->errorResponse('Unauthorized', [], 401);
        }

        $files = $request->getFiles();
        if (empty($files['image'])) {
            return $this->errorResponse('No image file provided');
        }

        $file = $files['image'];

        // Validate file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->errorResponse('File upload failed');
        }

        // Validate file type
        if (!in_array($file['type'], $this->allowedImageTypes)) {
            return $this->errorResponse('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.');
        }

        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            return $this->errorResponse('File size exceeds maximum allowed size of 10MB.');
        }

        // Verify it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return $this->errorResponse('Invalid image file.');
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'img_' . uniqid() . '_' . time() . '.' . $extension;
        
        // Define upload directory
        $uploadType = $request->getData('type') ?? 'content';
        $uploadDir = dirname(__DIR__, 4) . '/storage/uploads/' . $uploadType . '/';
        $yearMonth = date('Y/m');
        $fullUploadDir = $uploadDir . $yearMonth . '/';
        
        // Create directory if it doesn't exist
        if (!is_dir($fullUploadDir)) {
            if (!mkdir($fullUploadDir, 0755, true)) {
                return $this->errorResponse('Failed to create upload directory');
            }
        }

        $uploadPath = $fullUploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $this->errorResponse('Failed to save uploaded file');
        }

        // Generate thumbnail if needed
        $thumbnailUrl = null;
        if ($request->getData('generate_thumbnail') === 'true') {
            $thumbnailUrl = $this->generateThumbnail($uploadPath, $fullUploadDir, $filename);
        }

        // Return relative paths for database storage
        $imageUrl =  $uploadType . '/' . $yearMonth . '/' . $filename;
        
        // Log the upload
        // $this->mediaModel->logActivity($user['id'], 'image_uploaded', 'upload', 0, [
        //     'filename' => $filename,
        //     'size' => $file['size'],
        //     'type' => $file['type']
        // ]);

        return $this->successResponse([
            'url' => $imageUrl,
            'thumbnail_url' => $thumbnailUrl,
            'filename' => $filename,
            'size' => $file['size'],
            'type' => $file['type'],
            'dimensions' => [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1]
            ]
        ], 'Image uploaded successfully');
    }

    private function generateThumbnail(string $sourcePath, string $directory, string $filename): ?string
    {
        try {
            $imageInfo = getimagesize($sourcePath);
            if ($imageInfo === false) {
                return null;
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $type = $imageInfo[2];

            // Calculate thumbnail dimensions (max 300px width)
            $maxWidth = 300;
            if ($width > $maxWidth) {
                $ratio = $maxWidth / $width;
                $newWidth = $maxWidth;
                $newHeight = (int)($height * $ratio);
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }

            // Create source image resource
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($sourcePath);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($sourcePath);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($sourcePath);
                    break;
                case IMAGETYPE_TIFF:
                    $source = imagecreatefromgif($sourcePath);
                    break;
                case IMAGETYPE_AVIF:
                    $source = imagecreatefromgif($sourcePath);
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $source = imagecreatefromwebp($sourcePath);
                    } else {
                        return null;
                    }
                    break;
                default:
                    return null;
            }

            if (!$source) {
                return null;
            }

            // Create thumbnail
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG and GIF
            if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
                imagecolortransparent($thumbnail, imagecolorallocatealpha($thumbnail, 0, 0, 0, 127));
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
            }

            // Resize image
            imagecopyresampled(
                $thumbnail, $source,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $width, $height
            );

            // Save thumbnail
            $thumbnailFilename = 'thumb_' . $filename;
            $thumbnailPath = $directory . $thumbnailFilename;

            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($thumbnail, $thumbnailPath, 85);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($thumbnail, $thumbnailPath, 9);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($thumbnail, $thumbnailPath);
                    break;
                case IMAGETYPE_TIFF:
                    imagegif($thumbnail, $thumbnailPath);
                    break;
                case IMAGETYPE_AVIF:
                    imagegif($thumbnail, $thumbnailPath);
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagewebp')) {
                        imagewebp($thumbnail, $thumbnailPath, 85);
                    }
                    break;
            }

            // Clean up
            imagedestroy($source);
            imagedestroy($thumbnail);

            $yearMonth = date('Y/m');
            return ($this->request->getData('type') ?? 'content') . '/' . $yearMonth . '/' . $thumbnailFilename;

        } catch (\Exception $e) {
            error_log('Thumbnail generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete uploaded image - updated to handle storage path
     */
    public function deleteImage(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            return $this->errorResponse('Unauthorized', [], 401);
        }

        $data = $request->getBodyObject();
        
        if (empty($data['path'])) {
            return $this->errorResponse('No image path provided');
        }

        $imagePath = $data['path'];
        $type = $data['type'] ?? 'content';
        
        // Security: Validate the path to prevent directory traversal
        if (strpos($imagePath, '..') !== false || strpos($imagePath, '//') !== false) {
            return $this->errorResponse('Invalid image path');
        }
        
        // The path comes as: content/2025/06/filename.jpeg
        // Validate path format
        if (!preg_match('/^(content|news)\/\d{4}\/\d{2}\/[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|gif|webp)$/i', $imagePath)) {
            return $this->errorResponse('Invalid image path format');
        }
        
        // Construct full path in storage directory
        $baseDir = dirname(__DIR__, 4);
        $fullPath = $baseDir . '/storage/uploads/' . $imagePath;
        
        // Check if file exists
        if (!file_exists($fullPath)) {
            // Already deleted or doesn't exist
            return $this->successResponse([], 'Image already deleted or does not exist');
        }
        
        // Verify it's actually an image file
        $imageInfo = @getimagesize($fullPath);
        if ($imageInfo === false) {
            return $this->errorResponse('File is not a valid image');
        }
        
        // Delete the file
        try {
            if (unlink($fullPath)) {
                // Also delete thumbnail if exists
                $pathInfo = pathinfo($fullPath);
                $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
                if (file_exists($thumbnailPath)) {
                    @unlink($thumbnailPath);
                }
                
                // Log the deletion
                // $this->logActivity($user['id'], 'image_deleted', 'upload', null, [
                //     'path' => $imagePath,
                //     'type' => $type
                // ]);
                
                return $this->successResponse([], 'Image deleted successfully');
            } else {
                return $this->errorResponse('Failed to delete image');
            }
        } catch (\Exception $e) {
            error_log('Image deletion failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete image: ' . $e->getMessage());
        }
    }

    /**
     * Ensure storage link exists in public directory
     */
    private function ensureStorageLink(): void
    {
        $baseDir = dirname(__DIR__, 4);
        $publicStorageLink = $baseDir . '/storage';
        $storageDir = $baseDir . '/storage';
        
        if (!file_exists($publicStorageLink) && !is_link($publicStorageLink)) {
            // Try to create symbolic link
            if (function_exists('symlink')) {
                @symlink($storageDir, $publicStorageLink);
            }
        }
    }
    /**
     * Cleanup orphaned images (optional - for batch cleanup)
     */
    public function cleanupOrphanedImages(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user || $user['role'] !== 'super_admin') {
            return $this->errorResponse('Unauthorized', [], 401);
        }
        
        $type = $request->getQuery('type') ?? 'content';
        $olderThanDays = (int)($request->getQuery('older_than_days') ?? 7);
        
        $uploadDir = dirname(__DIR__, 4) . '/storage/uploads/' . $type;
        $deletedCount = 0;
        $errors = [];
        
        // Find old temporary images
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($uploadDir)
        );
        
        $cutoffTime = time() - ($olderThanDays * 24 * 60 * 60);
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() < $cutoffTime) {
                $relativePath = str_replace(dirname(__DIR__, 4) . '/storage', '', $file->getPathname());
                
                // Check if this image is referenced in any content
                if (!$this->isImageReferenced($relativePath, $type)) {
                    try {
                        if (unlink($file->getPathname())) {
                            $deletedCount++;
                        }
                    } catch (\Exception $e) {
                        $errors[] = $file->getFilename();
                    }
                }
            }
        }
        
        return $this->successResponse([
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ], "Cleaned up $deletedCount orphaned images");
    }

    /**
     * Check if image is referenced in content
     */
    private function isImageReferenced(string $imagePath, string $type): bool
    {
        // This is a simplified check - implement based on your database structure
        
        if ($type === 'content') {
            // Check in news content
            $sql = "SELECT COUNT(*) as count FROM news 
                    WHERE content LIKE ? OR featured_image = ?";
            $result = $this->db->fetch($sql, ['%' . $imagePath . '%', $imagePath]);
            
            if ($result['count'] > 0) {
                return true;
            }
            
            // Check in other content types that might use images
            // Add more checks as needed
        }
        
        return false;
    }

}