<?php

// admin/src/Controllers/SettingsController.php - Settings Controller

class SettingsController {
    private $db;
    private $user;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->user = AuthController::getCurrentUser();
    }

    public static function general() {
        $controller = new self();
        
        if (!AuthController::isLoggedIn()) {
            redirect(Router::url('login'));
            return;
        }

        // Get current settings
        $settings = $controller->getSettings([
            'site_name', 'site_description', 'site_keywords', 'posts_per_page',
            'allow_comments', 'moderate_comments', 'maintenance_mode'
        ]);

        // Set page data
        LayoutHelper::setPageData(
            'General Settings',
            "Configure your site's basic information and behavior",
            [
                ['label' => 'General Settings']
            ]
        );
        
        // Render view
        LayoutHelper::render('settings/general', [
            'settings' => $settings
        ]);
    }

    public static function updateGeneral() {
        $controller = new self();
        
        if (!AuthController::isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(Router::url('settings/general'));
            return;
        }

        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid security token';
            $settings = $controller->getSettings(['site_name', 'site_description', 'site_keywords', 'posts_per_page', 'allow_comments', 'moderate_comments', 'maintenance_mode']);
            $title = 'General Settings';
            redirect(Router::url('settings/general'));
            return;
        }

        $settingsToUpdate = [
            'site_name' => ['value' => $_POST['site_name'] ?? '', 'type' => 'string'],
            'site_description' => ['value' => $_POST['site_description'] ?? '', 'type' => 'string'],
            'site_keywords' => ['value' => $_POST['site_keywords'] ?? '', 'type' => 'string'],
            'posts_per_page' => ['value' => (int)($_POST['posts_per_page'] ?? 10), 'type' => 'integer'],
            'allow_comments' => ['value' => isset($_POST['allow_comments']) ? 1 : 0, 'type' => 'boolean'],
            'moderate_comments' => ['value' => isset($_POST['moderate_comments']) ? 1 : 0, 'type' => 'boolean'],
            'maintenance_mode' => ['value' => isset($_POST['maintenance_mode']) ? 1 : 0, 'type' => 'boolean']
        ];

        if ($controller->updateSettings($settingsToUpdate)) {
            $success = 'General settings updated successfully';
        } else {
            $error = 'Failed to update settings';
        }

        $settings = $controller->getSettings(array_keys($settingsToUpdate));
        // Set page data
        LayoutHelper::setPageData(
            'General Settings',
            'Configure General settings',
            [
                ['label' => 'General Settings']
            ]
        );
        
        // Render view
        LayoutHelper::render('settings/general', [
            'settings' => $settings
        ]);
    }

    public static function email() {
        $controller = new self();
        
        if (!AuthController::isLoggedIn()) {
            redirect(Router::url('login'));
            return;
        }

        $settings = $controller->getSettings([
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
            'mail_from_address', 'mail_from_name', 'email_notifications'
        ]);
        
        // Set page data
        LayoutHelper::setPageData(
            'Email Settings',
            'Configure SMTP settings and email notifications',
            [
                ['label' => 'Email Settings']
            ]
        );
        
        // Render view
        LayoutHelper::render('settings/email', [
            'settings' => $settings
        ]);
    }

    public static function updateEmail() {
        $controller = new self();
        
        if (!AuthController::isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(Router::url('settings/email'));
            return;
        }

        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid security token';
            $settings = $controller->getSettings(['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'mail_from_address', 'mail_from_name', 'email_notifications']);
            // Set page data
            LayoutHelper::setPageData(
                'Email Settings',
                'Configure SMTP settings and email notifications',
                [
                    ['label' => 'Email Settings']
                ]
            );
            
            // Render view
            LayoutHelper::render('settings/email', [
                'settings' => $settings
            ]);
        }

        $settingsToUpdate = [
            'smtp_host' => ['value' => $_POST['smtp_host'] ?? '', 'type' => 'string'],
            'smtp_port' => ['value' => (int)($_POST['smtp_port'] ?? 587), 'type' => 'integer'],
            'smtp_username' => ['value' => $_POST['smtp_username'] ?? '', 'type' => 'string'],
            'mail_from_address' => ['value' => $_POST['mail_from_address'] ?? '', 'type' => 'string'],
            'mail_from_name' => ['value' => $_POST['mail_from_name'] ?? '', 'type' => 'string'],
            'email_notifications' => ['value' => isset($_POST['email_notifications']) ? 1 : 0, 'type' => 'boolean']
        ];

        // Only update password if provided
        if (!empty($_POST['smtp_password'])) {
            $settingsToUpdate['smtp_password'] = ['value' => $_POST['smtp_password'], 'type' => 'string'];
        }

        if ($controller->updateSettings($settingsToUpdate)) {
            $success = 'Email settings updated successfully';
        } else {
            $error = 'Failed to update email settings';
        }

        $settings = $controller->getSettings(array_keys($settingsToUpdate));
        // Set page data
        LayoutHelper::setPageData(
            'Email Settings',
            'Configure SMTP settings and email notifications',
            [
                ['label' => 'Email Settings']
            ]
        );
        
        // Render view
        LayoutHelper::render('settings/email', [
            'settings' => $settings
        ]);
    }

    public static function api() {
        $controller = new self();
        
        if (!AuthController::isLoggedIn()) {
            redirect(Router::url('login'));
            return;
        }

        $settings = $controller->getSettings([
            'api_rate_limit_per_minute', 'api_rate_limit_per_hour',
            'jwt_access_expire', 'jwt_refresh_expire'
        ]);

        // Set page data
        LayoutHelper::setPageData(
            'API Settings',
            'Configure API rate limiting and JWT token settings',
            [
                ['label' => 'API Settings']
            ]
        );
        
        // Render view
        LayoutHelper::render('settings/api', [
            'settings' => $settings
        ]);
    }

    public static function updateApi() {
        $controller = new self();
        
        if (!AuthController::isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(Router::url('settings/api'));
            return;
        }

        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid security token';
            $settings = $controller->getSettings(['api_rate_limit_per_minute', 'api_rate_limit_per_hour', 'jwt_access_expire', 'jwt_refresh_expire']);
            // Set page data
            LayoutHelper::setPageData(
                'API Settings',
                'Configure API rate limiting and JWT token settings',
                [
                    ['label' => 'API Settings']
                ]
            );
            
            // Render view
            LayoutHelper::render('settings/api', [
                'settings' => $settings
            ]);
            return;
        }

        $settingsToUpdate = [
            'api_rate_limit_per_minute' => ['value' => (int)($_POST['api_rate_limit_per_minute'] ?? 60), 'type' => 'integer'],
            'api_rate_limit_per_hour' => ['value' => (int)($_POST['api_rate_limit_per_hour'] ?? 3600), 'type' => 'integer'],
            'jwt_access_expire' => ['value' => (int)($_POST['jwt_access_expire'] ?? 3600), 'type' => 'integer'],
            'jwt_refresh_expire' => ['value' => (int)($_POST['jwt_refresh_expire'] ?? 604800), 'type' => 'integer']
        ];

        if ($controller->updateSettings($settingsToUpdate)) {
            $success = 'API settings updated successfully';
        } else {
            $error = 'Failed to update API settings';
        }

        $settings = $controller->getSettings(array_keys($settingsToUpdate));
        // Set page data
        LayoutHelper::setPageData(
            'API Settings',
            'Configure API rate limiting and JWT token settings',
            [
                ['label' => 'API Settings']
            ]
        );
        
        // Render view
        LayoutHelper::render('settings/api', [
            'settings' => $settings
        ]);
    }

    public static function testEmail() {
        $controller = new self();
        
        header('Content-Type: application/json');
        
        if (!AuthController::isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            return;
        }

        $testEmail = $_POST['test_email'] ?? '';
        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Valid email address is required']);
            return;
        }

        // Get email settings
        $settings = $controller->getSettings(['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'mail_from_address', 'mail_from_name']);

        try {
            // Test email sending (implement actual email sending logic here)
            $result = $controller->sendTestEmail($testEmail, $settings);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Test email sent successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send test email']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()]);
        }
    }

    private function getSettings($keys) {
        if (empty($keys)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($keys) - 1) . '?';
        $results = $this->db->fetchAll("SELECT setting_key, setting_value, setting_type FROM settings WHERE setting_key IN ($placeholders)", $keys);
        
        $settings = [];
        foreach ($results as $row) {
            $value = $row['setting_value'];
            
            // Cast value based on type
            switch ($row['setting_type']) {
                case 'integer':
                    $value = (int)$value;
                    break;
                case 'boolean':
                    $value = in_array(strtolower($value), ['1', 'true', 'yes', 'on']);
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
            }
            
            $settings[$row['setting_key']] = $value;
        }
        
        // Set default values for missing settings
        $defaults = [
            'site_name' => 'NorthCity',
            'site_description' => 'Your trusted source for news and events',
            'site_keywords' => 'news, events, community',
            'posts_per_page' => 10,
            'allow_comments' => true,
            'moderate_comments' => true,
            'maintenance_mode' => false,
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'mail_from_address' => '',
            'mail_from_name' => 'NorthCity',
            'email_notifications' => true,
            'api_rate_limit_per_minute' => 60,
            'api_rate_limit_per_hour' => 3600,
            'jwt_access_expire' => 3600,
            'jwt_refresh_expire' => 604800
        ];
        
        foreach ($keys as $key) {
            if (!isset($settings[$key]) && isset($defaults[$key])) {
                $settings[$key] = $defaults[$key];
            }
        }
        
        return $settings;
    }

    private function updateSettings($settingsData) {
        try {
            $this->db->beginTransaction();
            
            foreach ($settingsData as $key => $data) {
                $value = $data['value'];
                $type = $data['type'];
                
                // Convert value to string for storage
                switch ($type) {
                    case 'boolean':
                        $value = $value ? '1' : '0';
                        break;
                    case 'json':
                        $value = json_encode($value);
                        break;
                    default:
                        $value = (string)$value;
                }
                
                $this->db->execute("
                    INSERT INTO settings (setting_key, setting_value, setting_type, updated_at) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    setting_type = VALUES(setting_type),
                    updated_at = NOW()
                ", [$key, $value, $type]);
            }
            
            // Log activity
            $this->db->execute("INSERT INTO activity_logs (user_id, action, target_type, target_id, ip_address, user_agent, created_at) VALUES (?, 'settings_updated', 'settings', 0, ?, ?, NOW())", [
                $this->user['id'],
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Settings update failed: " . $e->getMessage());
            return false;
        }
    }

    private function sendTestEmail($email, $settings) {
        // Simple test email implementation using PHP mail()
        // In production, you would use PHPMailer or similar
        
        $subject = 'Test Email from ' . ($settings['site_name'] ?? 'Admin Panel');
        $message = "
        <html>
        <body>
            <h2>Email Configuration Test</h2>
            <p>This is a test email to verify your email configuration is working correctly.</p>
            <p><strong>Sent from:</strong> " . ($_ENV['APP_NAME'] ?? 'Admin Panel') . "</p>
            <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p>If you received this email, your email configuration is working properly.</p>
        </body>
        </html>";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . ($settings['mail_from_name'] ?? 'Admin Panel') . ' <' . ($settings['mail_from_address'] ?? 'noreply@10.30.252.49') . '>' . "\r\n";
        
        // For basic testing, we'll use PHP's mail() function
        // In production, implement proper SMTP with the settings
        return mail($email, $subject, $message, $headers);
    }
}
