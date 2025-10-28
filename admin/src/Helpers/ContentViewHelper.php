<?php
// admin/src/Helpers/ContentViewHelper.php - Updated Helper with Tailwind CSS Classes

if (!defined('ADMIN_ACCESS')) {
    die('Direct access not permitted');
}

class ContentViewHelper
{
    public static function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 Bytes';
        
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($bytes, 1024));
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $sizes[$factor]);
    }
    
    public static function getStatusBadgeClass(string $status): string
    {
        $classes = [
            'approved' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800',
            'pending' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800',
            'flagged' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800',
            'rejected' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800'
            // 'hidden' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800'
        ];
        
        return $classes[$status] ?? 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800';
    }
    
    public static function getStatusText(string $status): string
    {
        $texts = [
            'approved' => 'APPROVED',
            'pending' => 'PENDING',
            'flagged' => 'FLAGGED',
            'rejected' => 'REJECTED'
            // 'hidden' => 'HIDDEN'
        ];
        
        return $texts[$status] ?? strtoupper($status);
    }
    
    public static function getStatusIcon(string $status): string
    {
        $icons = [
            'approved' => '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
            'pending' => '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'flagged' => '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"/></svg>',
            'rejected' => '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
            // 'hidden' => '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/></svg>'
        ];
        
        return $icons[$status] ?? $icons['pending'];
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
    
    public static function getMediaUrl(string $filePath): string
    {
        $baseUrl = defined('APP_URL') ? APP_URL : 'http://10.30.252.49';
        return rtrim($baseUrl, '/') . '/storage/uploads/media/' . $filePath;
    }
    
    public static function truncate(string $text, int $length): string
    {
        return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
    }
    
    public static function timeAgo(string $datetime): string
    {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time / 60) . ' minutes ago';
        if ($time < 86400) return floor($time / 3600) . ' hours ago';
        if ($time < 2592000) return floor($time / 86400) . ' days ago';
        if ($time < 31536000) return floor($time / 2592000) . ' months ago';
        
        return floor($time / 31536000) . ' years ago';
    }
    
    public static function formatDate(string $datetime): string
    {
        return date('M j, Y g:i A', strtotime($datetime));
    }
    
    public static function buildPaginationUrl(int $page): string
    {
        $params = $_GET;
        $params['page'] = $page;
        return Router::url('media') . '?' . http_build_query($params);
    }
    
    public static function getModerationActions(string $status): array
    {
        $actions = [
            'pending' => ['approve', 'reject', 'flag'],
            'approved' => ['flag', 'reject'],
            'rejected' => ['approve', 'flag'],
            'flagged' => ['approve', 'reject']
            // 'hidden' => ['approve', 'restore']
        ];
        
        return $actions[$status] ?? [];
    }
    
    public static function getActionButtonClass(string $action): string
    {
        $classes = [
            'approve' => 'inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors',
            'reject' => 'inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors',
            'flag' => 'inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors'
            // 'hide' => 'inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors',
            // 'restore' => 'inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors'
        ];
        
        return $classes[$action] ?? 'inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors';
    }
    
    public static function getActionIcon(string $action): string
    {
        $icons = [
            'approve' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
            'reject' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
            'flag' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 2H21l-3 6 3 6h-8.5l-1-2H5a2 2 0 00-2 2zm9-13.5V9"/></svg>'
            // 'hide' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/></svg>',
            // 'restore' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>'
        ];
        
        return $icons[$action] ?? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>';
    }
    
    public static function renderStatusBadge(string $status): string
    {
        $class = self::getStatusBadgeClass($status);
        $icon = self::getStatusIcon($status);
        $text = self::getStatusText($status);
        
        return "<span class=\"{$class}\">{$icon}{$text}</span>";
    }
    
    public static function renderModerationButton(string $action, int $mediaId): string
    {
        $class = self::getActionButtonClass($action);
        $icon = self::getActionIcon($action);
        $title = ucfirst($action);
        
        return "<button type=\"button\" class=\"{$class}\" onclick=\"moderateMedia({$mediaId}, '{$action}')\" title=\"{$title}\">{$icon}</button>";
    }
    
    public static function calculatePercentage(int $part, int $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 1) : 0;
    }
    
    public static function getModerationSummary(array $stats): array
    {
        $total = $stats['total_files'] ?? 0;
        $approved = $stats['approved_count'] ?? 0;
        $pending = $stats['pending_count'] ?? 0;
        $flagged = $stats['flagged_count'] ?? 0;
        $rejected = $stats['rejected_count'] ?? 0;
        
        return [
            'total' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'flagged' => $flagged,
            'rejected' => $rejected,
            'approval_rate' => self::calculatePercentage($approved, $total),
            'pending_rate' => self::calculatePercentage($pending, $total),
            'flag_rate' => self::calculatePercentage($flagged, $total),
            'needs_attention' => $pending + $flagged
        ];
    }
    
    public static function getStatusColor(string $status): string
    {
        $colors = [
            'approved' => 'green',
            'pending' => 'yellow',
            'flagged' => 'red',
            'rejected' => 'gray'
            // 'hidden' => 'purple'
        ];
        
        return $colors[$status] ?? 'gray';
    }
    
    public static function formatModerationAlert(array $stats): ?string
    {
        $pending = $stats['pending_count'] ?? 0;
        $flagged = $stats['flagged_count'] ?? 0;
        
        if ($pending > 0 || $flagged > 0) {
            $total = $pending + $flagged;
            $message = $total . ' item' . ($total !== 1 ? 's' : '') . ' need' . ($total === 1 ? 's' : '') . ' attention';
            
            return "<div class=\"bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6\">
                        <div class=\"flex items-center\">
                            <div class=\"flex-shrink-0\">
                                <svg class=\"w-5 h-5 text-yellow-400\" fill=\"currentColor\" viewBox=\"0 0 20 20\">
                                    <path fill-rule=\"evenodd\" d=\"M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z\" clip-rule=\"evenodd\"/>
                                </svg>
                            </div>
                            <div class=\"ml-3 flex-1\">
                                <p class=\"text-sm font-medium text-yellow-800\">
                                    {$message} - {$pending} pending review" . ($flagged > 0 ? ", {$flagged} flagged" : "") . "
                                </p>
                            </div>
                            <div class=\"ml-3\">
                                <a href=\"" . Router::url('media') . "?status=pending\" class=\"text-sm font-medium text-yellow-800 hover:text-yellow-900\">
                                    Review Now →
                                </a>
                            </div>
                        </div>
                    </div>";
        }
        
        return null;
    }
}

function _getContentTypeStyle($type) {
    return match($type) {
        'comment' => 'bg-blue-100 text-blue-600',
        'news' => 'bg-green-100 text-green-600',
        'event' => 'bg-purple-100 text-purple-600',
        'media' => 'bg-red-100 text-red-600',
        default => 'bg-gray-100 text-gray-600'
    };
}

function _getContentTypeIcon($type) {
    return match($type) {
        'comment' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
        'news' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>',
        'event' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
        'media' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
        default => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
    };
}

function _renderPriorityBadge($priority) {
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

// Helper functions for easier access in views
function _formatBytes(int $bytes): string {
    return ContentViewHelper::formatBytes($bytes);
}

function _getStatusBadgeClass(string $status): string {
    return ContentViewHelper::getStatusBadgeClass($status);
}

function _getStatusText(string $status): string {
    return ContentViewHelper::getStatusText($status);
}

function _getStatusIcon(string $status): string {
    return ContentViewHelper::getStatusIcon($status);
}

function _getFileTypeIcon(string $mimeType): string {
    return ContentViewHelper::getFileTypeIcon($mimeType);
}

function _getMediaUrl(string $filePath): string {
    return ContentViewHelper::getMediaUrl($filePath);
}

function _truncateText(string $text, int $length): string {
    return ContentViewHelper::truncate($text, $length);
}

function _timeAgo(string $datetime): string {
    return ContentViewHelper::timeAgo($datetime);
}

function _formatDate(string $datetime): string {
    return ContentViewHelper::formatDate($datetime);
}

function _buildPaginationUrl(int $page): string {
    return ContentViewHelper::buildPaginationUrl($page);
}

function _getModerationActions(string $status): array {
    return ContentViewHelper::getModerationActions($status);
}

function _getActionButtonClass(string $action): string {
    return ContentViewHelper::getActionButtonClass($action);
}

function _getActionIcon(string $action): string {
    return ContentViewHelper::getActionIcon($action);
}

function _renderStatusBadge(string $status): string {
    return ContentViewHelper::renderStatusBadge($status);
}

function _renderModerationButton(string $action, int $mediaId): string {
    return ContentViewHelper::renderModerationButton($action, $mediaId);
}

function _calculatePercentage(int $part, int $total): float {
    return ContentViewHelper::calculatePercentage($part, $total);
}

function _getModerationSummary(array $stats): array {
    return ContentViewHelper::getModerationSummary($stats);
}

function _getStatusColor(string $status): string {
    return ContentViewHelper::getStatusColor($status);
}

function _formatModerationAlert(array $stats): ?string {
    return ContentViewHelper::formatModerationAlert($stats);
}