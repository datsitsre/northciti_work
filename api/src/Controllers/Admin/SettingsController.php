<?php

// api/src/Controllers/Admin/SettingsController.php - Settings Admin Controller

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Setting;
use App\Services\CacheService;
use App\Services\EmailService;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;

class SettingsController extends BaseController
{
    private Setting $settingModel;
    private CacheService $cache;
    private EmailService $emailService;

    public function __construct(Setting $settingModel, CacheService $cache, EmailService $emailService)
    {
        $this->settingModel = $settingModel;
        $this->cache = $cache;
        $this->emailService = $emailService;
        parent::__construct();
    }

    public function index(Request $request): Response
    {
        // Only admin can access settings
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $settings = $this->settingModel->getAllSettings();

        // Group settings by category for better organization
        $groupedSettings = $this->groupSettings($settings);

        return $this->response->json([
            'success' => true,
            'data' => $groupedSettings
        ]);
    }

    public function update(Request $request): Response
    {
        // Only admin can update settings
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $data = $request->getData();
        
        if (empty($data['settings']) || !is_array($data['settings'])) {
            return $this->response->json([
                'success' => false,
                'message' => 'Settings data is required'
            ], 400);
        }

        // Validate settings
        $validation = $this->validateSettings($data['settings']);
        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        // Update settings
        $success = $this->settingModel->updateMultiple($data['settings']);
        
        if (!$success) {
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to update settings'
            ], 400);
        }

        // Clear cache
        $this->cache->clear();

        // Log activity
        $this->logActivity($request->user['id'], 'settings_updated', 'settings', 0);

        return $this->response->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    public function testEmail(Request $request): Response
    {
        // Only admin can test email
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $data = $request->getData();
        
        if (empty($data['email'])) {
            return $this->response->json([
                'success' => false,
                'message' => 'Email address is required'
            ], 400);
        }

        $emailData = [
            'to_email' => $data['email'],
            'to_name' => 'Test Recipient',
            'subject' => 'Test Email from ' . $this->settingModel->getSiteName(),
            'body_html' => $this->getTestEmailTemplate(),
            'priority' => 'high'
        ];

        try {
            $success = $this->emailService->send($emailData);
            
            if ($success) {
                return $this->response->json([
                    'success' => true,
                    'message' => 'Test email sent successfully'
                ]);
            } else {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Failed to send test email'
                ], 400);
            }
        } catch (\Exception $e) {
            return $this->response->json([
                'success' => false,
                'message' => 'Email sending failed: ' . $e->getMessage()
            ], 400);
        }
    }

    public function clearCache(Request $request): Response
    {
        // Only admin can clear cache
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $cleared = $this->cache->clear();
        
        if ($cleared) {
            // Log activity
            $this->logActivity($request->user['id'], 'cache_cleared', 'system', 0);

            return $this->response->json([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);
        } else {
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to clear cache'
            ], 400);
        }
    }

    public function backup(Request $request): Response
    {
        // Only admin can create backups
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        try {
            $backupFile = $this->createDatabaseBackup();
            
            // Log activity
            $this->logActivity($request->user['id'], 'backup_created', 'system', 0, [
                'backup_file' => $backupFile
            ]);

            return $this->response->json([
                'success' => true,
                'message' => 'Database backup created successfully',
                'data' => [
                    'backup_file' => $backupFile,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function emailQueue(Request $request): Response
    {
        // Only admin can view email queue
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 20), 100);
        $status = $request->getQuery('status');
        
        $offset = ($page - 1) * $perPage;
        $whereClause = '';
        $params = [];
        
        if ($status) {
            $whereClause = 'WHERE status = ?';
            $params[] = $status;
        }

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM email_queue {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];

        // Get emails
        $sql = "SELECT * FROM email_queue {$whereClause} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $emails = $this->db->fetchAll($sql, $params);

        return $this->response->json([
            'success' => true,
            'data' => $emails,
            'meta' => [
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => (int)$total,
                    'total_pages' => ceil($total / $perPage),
                    'has_next' => $page < ceil($total / $perPage),
                    'has_previous' => $page > 1
                ]
            ]
        ]);
    }

    public function processEmailQueue(Request $request): Response
    {
        // Only admin can process email queue
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $limit = min((int)($request->getData('limit') ?? 10), 100);
        
        try {
            $processed = $this->emailService->processQueue($limit);
            
            return $this->response->json([
                'success' => true,
                'message' => "Processed {$processed} emails from queue",
                'data' => [
                    'processed' => $processed
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to process email queue: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteQueuedEmail(Request $request, string $id): Response
    {
        // Only admin can delete queued emails
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $emailId = (int)$id;
        
        $sql = "DELETE FROM email_queue WHERE id = ?";
        $deleted = $this->db->execute($sql, [$emailId]);
        
        if ($deleted) {
            return $this->response->json([
                'success' => true,
                'message' => 'Queued email deleted successfully'
            ]);
        } else {
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to delete queued email'
            ], 400);
        }
    }

    private function groupSettings(array $settings): array
    {
        $groups = [
            'general' => [
                'title' => 'General Settings',
                'settings' => []
            ],
            'email' => [
                'title' => 'Email Settings',
                'settings' => []
            ],
            'api' => [
                'title' => 'API Settings',
                'settings' => []
            ],
            'security' => [
                'title' => 'Security Settings',
                'settings' => []
            ],
            'content' => [
                'title' => 'Content Settings',
                'settings' => []
            ],
            'other' => [
                'title' => 'Other Settings',
                'settings' => []
            ]
        ];

        foreach ($settings as $key => $setting) {
            $group = $this->getSettingGroup($key);
            $groups[$group]['settings'][$key] = $setting;
        }

        // Remove empty groups
        return array_filter($groups, function($group) {
            return !empty($group['settings']);
        });
    }

    private function getSettingGroup(string $key): string
    {
        $emailKeys = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'mail_from_address', 'mail_from_name'];
        $apiKeys = ['api_rate_limit_per_minute', 'api_rate_limit_per_hour', 'jwt_secret', 'jwt_access_expire', 'jwt_refresh_expire'];
        $securityKeys = ['maintenance_mode', 'recaptcha_site_key', 'recaptcha_secret_key'];
        $contentKeys = ['posts_per_page', 'allow_comments', 'moderate_comments'];
        $generalKeys = ['site_name', 'site_description', 'site_keywords'];

        if (in_array($key, $emailKeys)) return 'email';
        if (in_array($key, $apiKeys)) return 'api';
        if (in_array($key, $securityKeys)) return 'security';
        if (in_array($key, $contentKeys)) return 'content';
        if (in_array($key, $generalKeys)) return 'general';
        
        return 'other';
    }

    private function validateSettings(array $settings): array
    {
        $errors = [];

        foreach ($settings as $key => $data) {
            $value = is_array($data) ? $data['value'] : $data;
            
            switch ($key) {
                case 'site_name':
                    if (empty($value)) {
                        $errors[$key] = 'Site name is required';
                    } elseif (strlen($value) > 100) {
                        $errors[$key] = 'Site name must not exceed 100 characters';
                    }
                    break;
                    
                case 'posts_per_page':
                    if (!is_numeric($value) || $value < 1 || $value > 100) {
                        $errors[$key] = 'Posts per page must be between 1 and 100';
                    }
                    break;
                    
                case 'smtp_port':
                    if (!is_numeric($value) || $value < 1 || $value > 65535) {
                        $errors[$key] = 'SMTP port must be between 1 and 65535';
                    }
                    break;
                    
                case 'mail_from_address':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$key] = 'Invalid email address format';
                    }
                    break;
                    
                case 'api_rate_limit_per_minute':
                case 'api_rate_limit_per_hour':
                    if (!is_numeric($value) || $value < 1) {
                        $errors[$key] = 'Rate limit must be a positive number';
                    }
                    break;
                    
                case 'jwt_access_expire':
                case 'jwt_refresh_expire':
                    if (!is_numeric($value) || $value < 60) {
                        $errors[$key] = 'Token expiration must be at least 60 seconds';
                    }
                    break;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    private function getTestEmailTemplate(): string
    {
        return "
        <html>
        <body>
            <h1>Email Configuration Test</h1>
            <p>This is a test email to verify your email configuration is working correctly.</p>
            <p><strong>Server Details:</strong></p>
            <ul>
                <li>Sent from: " . $this->settingModel->getSiteName() . "</li>
                <li>Timestamp: " . date('Y-m-d H:i:s') . "</li>
                <li>Server: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "</li>
            </ul>
            <p>If you received this email, your email configuration is working properly.</p>
        </body>
        </html>";
    }

    private function createDatabaseBackup(): string
    {
        $dbHost = $_ENV['DB_HOST'];
        $dbName = $_ENV['DB_NAME'];
        $dbUser = $_ENV['DB_USER'];
        $dbPass = $_ENV['DB_PASS'];
        
        $backupDir = dirname(__DIR__, 3) . '/storage/backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = "backup_{$dbName}_{$timestamp}.sql";
        $backupPath = $backupDir . $backupFile;
        
        $command = "mysqldump --host={$dbHost} --user={$dbUser} --password={$dbPass} {$dbName} > {$backupPath}";
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception('Database backup failed');
        }
        
        return $backupFile;
    }

    private function logActivity(int $userId, string $action, string $targetType, int $targetId, array $metadata = []): void
    {
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
    }
}
