<?php

// admin/src/Models/Media.php - Admin Media Model

declare(strict_types=1);

class MediaModel
{
    private $db;
    protected string $table = 'media';
    
    public function __construct() 
    {
        $this->db = Database::getInstance();
    }
    
    public function getByUploader(int $uploaderId, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT m.*, u.username, u.first_name, u.last_name 
                FROM {$this->table} m
                LEFT JOIN users u ON m.uploader_id = u.id
                WHERE m.uploader_id = ? AND m.deleted_at IS NULL
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$uploaderId, $limit, $offset]);
    }

    public function getByType(string $fileType, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT m.*, u.username, u.first_name, u.last_name 
                FROM {$this->table} m
                LEFT JOIN users u ON m.uploader_id = u.id
                WHERE m.file_type = ? AND m.deleted_at IS NULL
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$fileType, $limit, $offset]);
    }

    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        $searchTerm = "%{$query}%";
        $sql = "SELECT m.*, u.username, u.first_name, u.last_name 
                FROM {$this->table} m
                LEFT JOIN users u ON m.uploader_id = u.id
                WHERE (m.original_filename LIKE ? OR m.alt_text LIKE ? OR m.caption LIKE ?) 
                AND m.deleted_at IS NULL
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $limit, $offset]);
    }

    public function getStatistics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_files,
                    SUM(file_size) as total_size,
                    COUNT(CASE WHEN file_type = 'image' THEN 1 END) as image_count,
                    COUNT(CASE WHEN file_type = 'video' THEN 1 END) as video_count,
                    COUNT(CASE WHEN file_type = 'audio' THEN 1 END) as audio_count,
                    COUNT(CASE WHEN file_type = 'document' THEN 1 END) as document_count,
                    COUNT(CASE WHEN file_type = 'other' THEN 1 END) as other_count,
                    AVG(file_size) as avg_file_size,
                    SUM(download_count) as total_downloads
                FROM {$this->table} 
                WHERE deleted_at IS NULL";
        
        return $this->db->fetch($sql) ?: [];
    }

    public function getPendingReview(): array
    {
        $sql = "SELECT m.*, u.username, u.first_name, u.last_name 
                FROM {$this->table} m
                LEFT JOIN users u ON m.uploader_id = u.id
                WHERE m.status = 'pending' AND m.deleted_at IS NULL
                ORDER BY m.created_at ASC";
        
        return $this->db->fetchAll($sql);
    }

    public function getFlagged(): array
    {
        $sql = "SELECT m.*, u.username, u.first_name, u.last_name,
                       COUNT(f.id) as flag_count
                FROM {$this->table} m
                LEFT JOIN users u ON m.uploader_id = u.id
                LEFT JOIN media_flags f ON m.id = f.media_id
                WHERE m.status = 'flagged' AND m.deleted_at IS NULL
                GROUP BY m.id
                ORDER BY flag_count DESC, m.created_at DESC";
        
        return $this->db->fetchAll($sql);
    }

    public function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) return '0 Bytes';
        
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($bytes, 1024));
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $sizes[$factor]);
    }

    public function getFileTypeIcon(string $mimeType): string
    {
        $icons = [
            'image' => 'fas fa-image',
            'video' => 'fas fa-video',
            'audio' => 'fas fa-music',
            'application/pdf' => 'fas fa-file-pdf',
            'application/msword' => 'fas fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fas fa-file-word',
            'application/vnd.ms-excel' => 'fas fa-file-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fas fa-file-excel',
            'text/plain' => 'fas fa-file-alt',
            'application/zip' => 'fas fa-file-archive',
            'application/x-rar-compressed' => 'fas fa-file-archive'
        ];

        foreach ($icons as $type => $icon) {
            if (strpos($mimeType, $type) !== false) {
                return $icon;
            }
        }

        return 'fas fa-file';
    }
}