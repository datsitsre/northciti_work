 <?php

// admin/src/Helpers/ModerationHelper.php - Helper functions for unified moderation

class ModerationHelper
{
    /**
     * Get content type styling for UI
     */
    public static function getContentTypeStyle(string $type): string
    {
        return match($type) {
            'comment' => 'bg-blue-100 text-blue-600',
            'news' => 'bg-green-100 text-green-600',
            'event' => 'bg-purple-100 text-purple-600',
            'media' => 'bg-red-100 text-red-600',
            default => 'bg-gray-100 text-gray-600'
        };
    }

    /**
     * Get content type icon SVG
     */
    public static function getContentTypeIcon(string $type): string
    {
        return match($type) {
            'comment' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
            'news' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>',
            'event' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
            'media' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
            default => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
        };
    }

    /**
     * Render status badge with appropriate styling
     */
    public static function renderStatusBadge(string $status): string
    {
        $colors = match($status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'flagged' => 'bg-red-100 text-red-800',
            'published' => 'bg-green-100 text-green-800',
            'archived' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
        
        return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$colors}\">" . ucfirst($status) . "</span>";
    }

    /**
     * Render priority badge with appropriate styling
     */
    public static function renderPriorityBadge(?string $priority): string
    {
        if (!$priority) return '';
        
        $colors = match($priority) {
            'urgent' => 'bg-red-100 text-red-800',
            'high' => 'bg-orange-100 text-orange-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'low' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800'
        };
        
        return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$colors}\">" . ucfirst($priority) . "</span>";
    }

    /**
     * Build pagination URL with current filters
     */
    public static function buildPaginationUrl(int $page): string
    {
        $params = $_GET;
        $params['page'] = $page;
        return Router::url('moderation') . '?' . http_build_query($params);
    }

    /**
     * Format time ago for display
     */
    public static function timeAgo(string $datetime): string
    {
        $time = time() - strtotime($datetime);
        $units = [
            31536000 => 'year',
            2592000 => 'month', 
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        ];
        
        foreach ($units as $unit => $text) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '') . ' ago';
        }
        
        return 'just now';
    }

    /**
     * Get moderation score color class
     */
    public static function getModerationScoreClass(float $score): string
    {
        if ($score >= 0.8) return 'text-red-600 bg-red-100';
        if ($score >= 0.6) return 'text-orange-600 bg-orange-100';
        if ($score >= 0.3) return 'text-yellow-600 bg-yellow-100';
        return 'text-green-600 bg-green-100';
    }

    /**
     * Format moderation flags for display
     */
    public static function formatModerationFlags(?string $flags): array
    {
        if (!$flags) return [];
        
        try {
            $flagsArray = json_decode($flags, true);
            return is_array($flagsArray) ? $flagsArray : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Format moderation reasons for display
     */
    public static function formatModerationReasons(?string $reasons): array
    {
        if (!$reasons) return [];
        
        try {
            $reasonsArray = json_decode($reasons, true);
            return is_array($reasonsArray) ? $reasonsArray : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get content preview based on type
     */
    public static function getContentPreview(array $item): string
    {
        return match($item['content_type']) {
            'comment' => self::truncateText($item['content'] ?? '', 100),
            'news' => $item['title'] ?? 'Untitled News',
            'event' => $item['title'] ?? 'Untitled Event', 
            'media' => $item['original_filename'] ?? 'Unknown File',
            default => 'Unknown Content'
        };
    }

    /**
     * Truncate text with ellipsis
     */
    public static function truncateText(string $text, int $length = 100): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . '...';
    }

    /**
     * Get author display name
     */
    public static function getAuthorDisplayName(array $item): string
    {
        if (!empty($item['first_name']) && !empty($item['last_name'])) {
            return $item['first_name'] . ' ' . $item['last_name'];
        }
        
        if (!empty($item['username'])) {
            return $item['username'];
        }
        
        if (!empty($item['author_name'])) {
            return $item['author_name'];
        }
        
        return 'Anonymous';
    }

    /**
     * Check if content is flagged
     */
    public static function isContentFlagged(array $item): bool
    {
        return !empty($item['is_flagged']) || 
               !empty($item['flag_count']) || 
               !empty($item['flags']);
    }

    /**
     * Get moderation action color class
     */
    public static function getModerationActionColor(string $action): string
    {
        return match($action) {
            'approve' => 'bg-green-600 hover:bg-green-700 text-white',
            'reject' => 'bg-red-600 hover:bg-red-700 text-white',
            'flag' => 'bg-yellow-600 hover:bg-yellow-700 text-white',
            'hide' => 'bg-gray-600 hover:bg-gray-700 text-white',
            'delete' => 'bg-red-700 hover:bg-red-800 text-white',
            default => 'bg-gray-600 hover:bg-gray-700 text-white'
        };
    }

    /**
     * Get content type statistics
     */
    public static function getContentTypeStats(array $stats): array
    {
        $total = ($stats['pending_comments'] ?? 0) + 
                ($stats['pending_news'] ?? 0) + 
                ($stats['pending_events'] ?? 0) + 
                ($stats['pending_media'] ?? 0);

        if ($total === 0) {
            return [
                'comment' => ['count' => 0, 'percentage' => 0],
                'news' => ['count' => 0, 'percentage' => 0],
                'event' => ['count' => 0, 'percentage' => 0],
                'media' => ['count' => 0, 'percentage' => 0]
            ];
        }

        return [
            'comment' => [
                'count' => $stats['pending_comments'] ?? 0,
                'percentage' => round((($stats['pending_comments'] ?? 0) / $total) * 100, 1)
            ],
            'news' => [
                'count' => $stats['pending_news'] ?? 0,
                'percentage' => round((($stats['pending_news'] ?? 0) / $total) * 100, 1)
            ],
            'event' => [
                'count' => $stats['pending_events'] ?? 0,
                'percentage' => round((($stats['pending_events'] ?? 0) / $total) * 100, 1)
            ],
            'media' => [
                'count' => $stats['pending_media'] ?? 0,
                'percentage' => round((($stats['pending_media'] ?? 0) / $total) * 100, 1)
            ]
        ];
    }

    /**
     * Format file size for display
     */
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get risk level based on moderation score
     */
    public static function getRiskLevel(float $score): string
    {
        if ($score >= 0.8) return 'Critical';
        if ($score >= 0.6) return 'High';
        if ($score >= 0.3) return 'Medium';
        return 'Low';
    }

    /**
     * Generate moderation summary
     */
    public static function generateModerationSummary(array $stats): array
    {
        $totalPending = ($stats['pending_count'] ?? 0);
        $totalProcessed = ($stats['approved_count'] ?? 0) + ($stats['rejected_count'] ?? 0);
        $approvalRate = $stats['approval_rate'] ?? 0;
        
        return [
            'total_pending' => $totalPending,
            'total_processed' => $totalProcessed,
            'approval_rate' => $approvalRate,
            'workload_status' => self::getWorkloadStatus($totalPending),
            'efficiency_rating' => self::getEfficiencyRating($approvalRate, $stats['avg_review_time'] ?? 0)
        ];
    }

    /**
     * Get workload status based on pending count
     */
    private static function getWorkloadStatus(int $pending): string
    {
        if ($pending === 0) return 'Clear';
        if ($pending <= 10) return 'Light';
        if ($pending <= 50) return 'Moderate';
        if ($pending <= 100) return 'Heavy';
        return 'Critical';
    }

    /**
     * Get efficiency rating
     */
    private static function getEfficiencyRating(float $approvalRate, float $avgReviewTime): string
    {
        $score = 0;
        
        // Approval rate component (0-50 points)
        if ($approvalRate >= 80) $score += 50;
        elseif ($approvalRate >= 60) $score += 35;
        elseif ($approvalRate >= 40) $score += 20;
        else $score += 10;
        
        // Review time component (0-50 points)
        if ($avgReviewTime <= 2) $score += 50;
        elseif ($avgReviewTime <= 6) $score += 35;
        elseif ($avgReviewTime <= 12) $score += 20;
        else $score += 10;
        
        if ($score >= 80) return 'Excellent';
        if ($score >= 60) return 'Good';
        if ($score >= 40) return 'Fair';
        return 'Needs Improvement';
    }

    /**
     * Export data to CSV format
     */
    public static function exportToCsv(array $data, string $filename): string
    {
        $filepath = sys_get_temp_dir() . '/' . $filename;
        $file = fopen($filepath, 'w');
        
        if (!empty($data)) {
            // Write headers
            fputcsv($file, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
        }
        
        fclose($file);
        return $filepath;
    }
    

    public static function formatDate(string $datetime): string
    {
        return date('M j, Y g:i A', strtotime($datetime));
    }
    /**
     * Get available moderation actions for content type
     */
    public static function getAvailableActions(string $contentType): array
    {
        $baseActions = ['approve', 'reject', 'flag'];
        
        return match($contentType) {
            'comment' => [...$baseActions, 'delete'],
            'news' => [...$baseActions, 'hide'],
            'event' => [...$baseActions, 'hide'],
            'media' => [...$baseActions, 'hide', 'delete'],
            default => $baseActions
        };
    }

    /**
     * Validate moderation action
     */
    public static function isValidAction(string $action, string $contentType): bool
    {
        $availableActions = self::getAvailableActions($contentType);
        return in_array($action, $availableActions);
    }

    /**
     * Get content URL for viewing
     */
    public static function getContentUrl(array $item): ?string
    {
        return match($item['content_type']) {
            'news' => Router::url("news/show/{$item['content_id']}"),
            'event' => Router::url("events/show/{$item['content_id']}"),
            'media' => Router::url("media/show?id={$item['content_id']}"),
            'comment' => null, // Comments don't have standalone URLs
            default => null
        };
    }

    public static function getFileTypeIcon(string $mimeType): string
    {
        if (strpos($mimeType, 'image/') === 0) {
            return '<path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>';
        }
        
        if (strpos($mimeType, 'video/') === 0) {
            return '<path d="M2 6a2 2 0 012-2h6l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM4 8a1 1 0 000 2h1v3a1 1 0 001 1h3a1 1 0 001-1v-3h1a1 1 0 100-2H4z"/>';
        }
        
        if (strpos($mimeType, 'audio/') === 0) {
            return '<path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>';
        }
        
        if ($mimeType === 'application/pdf') {
            return '<path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z" clip-rule="evenodd"/>';
        }
        
        return '<path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z" clip-rule="evenodd"/>';
    }
}

function _renderMediaPreview($media) {
    if ($media['file_type'] === 'image') {
        return "<div class=\"text-center\">
                    <img src=\"" . _getMediaUrl($media['file_path']) . "\" 
                         alt=\"" . htmlspecialchars($media['original_filename']) . "\"
                         class=\"max-w-full h-auto max-h-96 mx-auto rounded-2xl shadow-2xl border border-gray-200 hover:shadow-3xl transition-all duration-300\">
                </div>";
    } elseif ($media['file_type'] === 'video') {
        return "<div class=\"text-center\">
                    <video controls class=\"max-w-full h-auto max-h-96 mx-auto rounded-2xl shadow-2xl border border-gray-200\">
                        <source src=\"" . _getMediaUrl($media['file_path']) . "\" type=\"{$media['mime_type']}\">
                        Your browser does not support the video tag.
                    </video>
                </div>";
    } else {
        return "<div class=\"text-center p-12 bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl border border-gray-200\">
                    <div class=\"w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-xl\">
                        <svg class=\"w-10 h-10 text-white\" fill=\"currentColor\" viewBox=\"0 0 20 20\">
                            <path d=\"M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z\"/>
                        </svg>
                    </div>
                    <h4 class=\"text-xl font-bold text-gray-900 mb-2\">" . htmlspecialchars($media['original_filename']) . "</h4>
                    <p class=\"text-gray-600 mb-6\">" . strtoupper(pathinfo($media['original_filename'], PATHINFO_EXTENSION)) . " • " . formatBytes($media['file_size']) . "</p>
                    <a href=\"" . _getMediaUrl($media['file_path']) . "\" target=\"_blank\" 
                       class=\"btn-modern btn-primary inline-flex items-center\">
                        <svg class=\"w-5 h-5 mr-2\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\"/>
                        </svg>
                        Download File
                    </a>
                </div>";
    }
}

function _renderArticlePreview($content, $type) {
    $featuredImage = !empty($content['featured_image']) ? 
        "<div class=\"mb-8\">
            <img src=\"" . htmlspecialchars($content['featured_image']) . "\" 
                 alt=\"Featured image\" 
                 class=\"w-full h-80 object-cover rounded-2xl shadow-2xl border border-gray-200\">
         </div>" : '';
    
    $eventDetails = '';
    if ($type === 'event') {
        $eventDetails = "<div class=\"bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-2xl p-6 mb-8\">
                            <div class=\"grid grid-cols-1 md:grid-cols-2 gap-4 text-sm\">
                                " . (!empty($content['start_date']) ? "<div class=\"flex items-center space-x-2\">
                                    <svg class=\"w-4 h-4 text-green-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z\"/>
                                    </svg>
                                    <span class=\"font-medium text-green-800\">Start: " . date('F j, Y', strtotime($content['start_date'])) . "</span>
                                </div>" : "") . "
                                " . (!empty($content['venue_name']) ? "<div class=\"flex items-center space-x-2\">
                                    <svg class=\"w-4 h-4 text-green-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z\"/>
                                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 11a3 3 0 11-6 0 3 3 0 016 0z\"/>
                                    </svg>
                                    <span class=\"font-medium text-green-800\">Venue: " . htmlspecialchars($content['venue_name']) . "</span>
                                </div>" : "") . "
                            </div>
                         </div>";
    }
    
    return "<div class=\"prose max-w-none\">
                <h2 class=\"text-3xl font-bold text-gray-900 mb-6 leading-tight\">" . htmlspecialchars($content['title'] ?? 'Untitled') . "</h2>
                {$featuredImage}
                {$eventDetails}
                <div class=\"text-gray-700 leading-relaxed text-lg\">" . nl2br(htmlspecialchars(substr($content['content'] ?? $content['description'] ?? 'No content available', 0, 500))) . "...</div>
            </div>";
}

function _renderCommentPreview($content) {
    return "<div class=\"bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-8 border border-blue-200\">
                <div class=\"flex items-start space-x-4\">
                    <div class=\"w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg\">
                        <svg class=\"w-6 h-6 text-white\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z\"/>
                        </svg>
                    </div>
                    <div class=\"flex-1\">
                        <div class=\"flex items-center space-x-2 mb-3\">
                            <span class=\"font-bold text-gray-900\">" . htmlspecialchars($content['author_name'] ?? 'Anonymous') . "</span>
                            <span class=\"text-gray-400\">•</span>
                            <span class=\"text-sm text-gray-600\">" . _timeAgo($content['created_at']) . "</span>
                        </div>
                        <div class=\"text-gray-800 text-lg leading-relaxed\">" . htmlspecialchars($content['content'] ?? 'No content') . "</div>
                        " . (($content['upvotes'] ?? 0) > 0 || ($content['downvotes'] ?? 0) > 0 ? 
                            "<div class=\"flex items-center space-x-6 mt-4\">
                                <span class=\"flex items-center space-x-1 text-green-600 font-medium\">
                                    <svg class=\"w-5 h-5\" fill=\"currentColor\" viewBox=\"0 0 20 20\">
                                        <path d=\"M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z\"/>
                                    </svg>
                                    <span>" . ($content['upvotes'] ?? 0) . "</span>
                                </span>
                                <span class=\"flex items-center space-x-1 text-red-600 font-medium\">
                                    <svg class=\"w-5 h-5 transform rotate-180\" fill=\"currentColor\" viewBox=\"0 0 20 20\">
                                        <path d=\"M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z\"/>
                                    </svg>
                                    <span>" . ($content['downvotes'] ?? 0) . "</span>
                                </span>
                            </div>" : "") . "
                    </div>
                </div>
            </div>";
}

function _renderUnknownPreview() {
    return "<div class=\"text-center p-16 bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl border border-gray-200\">
                <div class=\"w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-gray-400 to-gray-600 rounded-2xl flex items-center justify-center shadow-xl\">
                    <svg class=\"w-10 h-10 text-white\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\"/>
                    </svg>
                </div>
                <h4 class=\"text-xl font-bold text-gray-900 mb-2\">Unknown Content Type</h4>
                <p class=\"text-gray-600\">Unable to preview this content type</p>
            </div>";
}

function _renderContentDetails($content, $type, $id) {
    $details = [];
    
    // Common details
    $details[] = "<div class=\"bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors\">
                    <dt class=\"text-sm font-semibold text-gray-600 mb-1\">Content Type</dt>
                    <dd class=\"text-lg font-bold text-gray-900\">" . ucfirst($type) . "</dd>
                  </div>";
    
    $details[] = "<div class=\"bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors\">
                    <dt class=\"text-sm font-semibold text-gray-600 mb-1\">Content ID</dt>
                    <dd class=\"text-lg font-mono text-gray-900\">{$id}</dd>
                  </div>";
    
    // Type-specific details
    if ($type === 'media') {
        $details[] = "<div class=\"bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors\">
                        <dt class=\"text-sm font-semibold text-gray-600 mb-1\">File Size</dt>
                        <dd class=\"text-lg font-bold text-gray-900\">" . formatBytes($content['file_size']) . "</dd>
                      </div>";
        $details[] = "<div class=\"bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors\">
                        <dt class=\"text-sm font-semibold text-gray-600 mb-1\">Downloads</dt>
                        <dd class=\"text-lg font-bold text-gray-900\">" . number_format($content['download_count'] ?? 0) . "</dd>
                      </div>";
    } elseif ($type === 'news') {
        $details[] = "<div class=\"bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors\">
                        <dt class=\"text-sm font-semibold text-gray-600 mb-1\">Reading Time</dt>
                        <dd class=\"text-lg font-bold text-gray-900\">" . ($content['reading_time'] ?? 0) . " min</dd>
                      </div>";
    } elseif ($type === 'comment') {
        $details[] = "<div class=\"bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors\">
                        <dt class=\"text-sm font-semibold text-gray-600 mb-1\">Votes</dt>
                        <dd class=\"text-lg font-bold text-green-600\">+" . ($content['upvotes'] ?? 0) . " <span class=\"text-gray-400\">/</span> <span class=\"text-red-600\">-" . ($content['downvotes'] ?? 0) . "</span></dd>
                      </div>";
    }
    
    $details[] = "<div class=\"bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors\">
                    <dt class=\"text-sm font-semibold text-gray-600 mb-1\">Created</dt>
                    <dd class=\"text-sm font-medium text-gray-900\">" . _formatDate($content['created_at']) . "</dd>
                  </div>";
    
    return implode('', $details);
}

function _renderAuthorCard($content, $type) {
    $authorName = match($type) {
        'news' => $content['author_name'] ?? 'Unknown Author',
        'event' => $content['organizer_name'] ?? $content['actual_organizer_name'] ?? 'Unknown Organizer',
        'comment' => $content['author_name'] ?? 'Anonymous',
        'media' => $content['uploader_username'] ?? 'Unknown User',
        default => 'Unknown User'
    };
    
    $authorEmail = match($type) {
        'news' => $content['author_email'] ?? '',
        'event' => $content['organizer_email'] ?? $content['actual_organizer_email'] ?? '',
        'comment' => $content['author_email'] ?? '',
        'media' => $content['uploader_email'] ?? '',
        default => ''
    };
    
    $authorRole = match($type) {
        'news' => $content['author_role'] ?? 'contributor',
        'event' => $content['organizer_role'] ?? 'contributor',
        'comment' => $content['user_role'] ?? 'public',
        'media' => $content['uploader_role'] ?? 'public',
        default => 'public'
    };
    
    return "<div class=\"text-center\">
                <div class=\"w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-xl\">
                    <svg class=\"w-8 h-8 text-white\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z\"/>
                    </svg>
                </div>
                <h4 class=\"text-lg font-bold text-gray-900 mb-1\">" . htmlspecialchars($authorName) . "</h4>
                " . ($authorEmail ? "<p class=\"text-sm text-gray-600 mb-3\">" . htmlspecialchars($authorEmail) . "</p>" : "") . "
                <span class=\"status-pill bg-indigo-100 text-indigo-800\">" . strtoupper($authorRole) . "</span>
            </div>";
}

function _renderContentStats($content, $type) {
    $stats = [];
    
    if ($type === 'media') {
        $stats[] = "<div class=\"flex justify-between items-center p-3 bg-gray-50 rounded-xl\">
                        <span class=\"text-sm font-medium text-gray-600\">Downvotes</span>
                        <span class=\"text-lg font-bold text-red-600\">" . number_format($content['downvotes'] ?? 0) . "</span>
                    </div>";
    }
    
    $stats[] = "<div class=\"flex justify-between items-center p-3 bg-gray-50 rounded-xl\">
                    <span class=\"text-sm font-medium text-gray-600\">Created</span>
                    <span class=\"text-sm font-medium text-gray-900\">" . date('M j', strtotime($content['created_at'])) . "</span>
                </div>";
    
    if (!empty($content['is_flagged']) || !empty($content['flag_count'])) {
        $stats[] = "<div class=\"flex justify-between items-center p-3 bg-red-50 rounded-xl border border-red-200\">
                        <span class=\"text-sm font-medium text-red-600\">Flags</span>
                        <span class=\"text-lg font-bold text-red-700\">" . ($content['flag_count'] ?? 1) . "</span>
                    </div>";
    }
    
    return implode('', $stats);
}

function _getActionBgColor($action) {
    return match($action) {
        'approve', 'approved' => 'bg-gradient-to-br from-green-500 to-emerald-600',
        'reject', 'rejected' => 'bg-gradient-to-br from-red-500 to-red-600',
        'delete' => 'bg-gradient-to-br from-red-600 to-red-700',
        'flag' => 'bg-gradient-to-br from-yellow-500 to-yellow-600',
        default => 'bg-gradient-to-br from-gray-500 to-gray-600'
    };
}

function _getActionIcon($action) {
    return match($action) {
        'approve', 'approved' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
        'reject', 'rejected' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
        'delete' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
        'flag' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 2H21l-3 6 3 6h-8.5l-1-2H5a2 2 0 00-2 2zm9-13.5V9"/>',
        default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'
    };
}

// Global helper function wrappers for backward compatibility
function _getContentTypeStyle($type) {
    return ModerationHelper::getContentTypeStyle($type);
}

function _getContentTypeIcon($type) {
    return ModerationHelper::getContentTypeIcon($type);
}

function _renderStatusBadge($status) {
    return ModerationHelper::renderStatusBadge($status);
}

function _renderPriorityBadge($priority) {
    return ModerationHelper::renderPriorityBadge($priority);
}

function _buildPaginationUrl($page) {
    return ModerationHelper::buildPaginationUrl($page);
}

function _timeAgo($datetime) {
    return ModerationHelper::timeAgo($datetime);
}

function _getModerationScoreClass($score) {
    return ModerationHelper::getModerationScoreClass($score);
}

function _formatModerationFlags($flags) {
    return ModerationHelper::formatModerationFlags($flags);
}

function _getContentPreview($item) {
    return ModerationHelper::getContentPreview($item);
}

function _getAuthorDisplayName($item) {
    return ModerationHelper::getAuthorDisplayName($item);
}

function _isContentFlagged($item) {
    return ModerationHelper::isContentFlagged($item);
}

function _getFileTypeIcon(string $mimeType): string {
    return ModerationHelper::getFileTypeIcon($mimeType);
}

function _formatDate(string $datetime): string {
    return ModerationHelper::formatDate($datetime);
}