<?php

// admin/src/Helpers/LayoutHelper.php - Layout Helper Functions

class LayoutHelper 
{
    /**
     * Render a view with layout
     */
    public static function render($viewPath, $data = [], $layout = 'base')
    {
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view
        $fullViewPath = __DIR__ . "/../Views/{$viewPath}.php";
        if (file_exists($fullViewPath)) {
            include $fullViewPath;
        } else {
            throw new Exception("View file not found: {$fullViewPath}");
        }
        
        // Get the view content
        $content = ob_get_clean();
        
        // Include the layout
        $layoutPath = __DIR__ . "/../../templates/layouts/{$layout}.php";
        if (file_exists($layoutPath)) {
            include $layoutPath;
        } else {
            throw new Exception("Layout file not found: {$layoutPath}");
        }
    }
    
    /**
     * Add flash message to session
     */
    public static function addFlashMessage($message, $type = 'info', $title = null)
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        
        $_SESSION['flash_messages'][] = [
            'message' => $message,
            'type' => $type,
            'title' => $title,
            'timestamp' => time()
        ];
    }
    
    /**
     * Set page title and breadcrumbs
     */
    public static function setPageData($title, $subtitle = '', $breadcrumbs = [])
    {
        
        $GLOBALS['pageTitle'] = $title;
        $GLOBALS['pageSubtitle'] = $subtitle;
        $GLOBALS['breadcrumbs'] = $breadcrumbs;
    }
    

    /**
     * Format number for display
     */
    public static function formatNumber($number)
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        return number_format($number);
    }
    
    /**
     * Get time difference in human readable format
     */
    public static function timeAgo($datetime)
    {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . ' minutes ago';
        if ($time < 86400) return floor($time/3600) . ' hours ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        if ($time < 31104000) return floor($time/2592000) . ' months ago';
        
        return floor($time/31104000) . ' years ago';
    }
}

