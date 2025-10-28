<?php
// admin/src/Controllers/UserController.php - User Management Controller

require_once __DIR__ . '/../Helpers/LayoutHelper.php';
require_once __DIR__ . '/../Models/User.php';

class UserController 
{
    private $userModel;
    
    public function __construct() 
    {
        $this->userModel = new UserModel();
    }
    
    // List all users with pagination and search
    public static function index()
    {
        AuthController::requireAuth();
        
        $controller = new self();
        
        // Get parameters
        $page = max(1, (int)($_GET['page'] ?? 1));
        $search = trim($_GET['search'] ?? '');
        $role = $_GET['role'] ?? '';
        $status = $_GET['status'] ?? '';
        $perPage = 20;
        
        // Build filters
        $filters = [];
        if (!empty($role)) $filters['role'] = $role;
        if (!empty($status)) $filters['status'] = $status;
        
        // Get users
        if (!empty($search)) {
            $result = $controller->userModel->search($search, $page, $perPage);
        } else {
            $result = $controller->userModel->getFiltered($filters, $page, $perPage);
        }
        
        // Get statistics for filters
        $stats = $controller->userModel->getStatistics();
        
        // Set page data
        LayoutHelper::setPageData(
            'User Management',
            'Manage platform users and their permissions',
            [
                ['label' => 'Users']
            ]
        );
        
        // Render view
        LayoutHelper::render('users/index', [
            'users' => $result['data'],
            'pagination' => $result['pagination'],
            'search' => $search,
            'filters' => compact('role', 'status'),
            'stats' => $stats
        ]);
    }
    
    // Show individual user details
    public static function show($id)
    {
        AuthController::requireAuth();
        
        $controller = new self();
        $user = $controller->userModel->findById((int)$id);
        
        if (!$user) {
            LayoutHelper::addFlashMessage('User not found', 'error');
            redirect(Router::url('users'));
            return;
        }
        
        // Get user's content statistics
        $userStats = $controller->getUserContentStats((int)$id);
        
        // Get recent activity
        $recentActivity = $controller->getUserActivity((int)$id, 10);
        
        // Set page data
        LayoutHelper::setPageData(
            'User Details',
            $user['first_name'] . ' ' . $user['last_name'],
            [
                ['label' => 'Users', 'url' => Router::url('users')],
                ['label' => 'User Details']
            ]
        );
        
        // Render view
        LayoutHelper::render('users/show', [
            'user' => $user,
            'stats' => $userStats,
            'recentActivity' => $recentActivity
        ]);
    }
    
    // Show create user form
    public static function create()
    {
        AuthController::requireAuth();
        
        // Set page data
        LayoutHelper::setPageData(
            'Create User',
            'Add a new user to the platform',
            [
                ['label' => 'Users', 'url' => Router::url('users')],
                ['label' => 'Create User']
            ]
        );
        
        // Render view
        LayoutHelper::render('users/create', []);
    }
    
    // Handle create user form submission
    public static function store()
    {
        AuthController::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(Router::url('users/create'));
            return;
        }
        
        $controller = new self();
        
        try {
            // Validate CSRF token
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid security token');
            }
            
            // Validate input
            $data = $controller->validateUserData($_POST);
            
            // Check if email already exists
            if ($controller->userModel->findByEmail($data['email'])) {
                throw new Exception('Email address already exists');
            }
            
            // Check if username already exists
            if ($controller->userModel->findByUsername($data['username'])) {
                throw new Exception('Username already exists');
            }
            
            // Create user
            $user = $controller->userModel->create($data);
            
            if ($user) {
                // Log activity
                $controller->logActivity('user_created', $user['id'], [
                    'user_email' => $user['email'],
                    'user_role' => $user['role']
                ]);
                
                LayoutHelper::addFlashMessage('User created successfully', 'success');
                redirect(Router::url('users/' . $user['id']));
            } else {
                throw new Exception('Failed to create user');
            }
            
        } catch (Exception $e) {
            LayoutHelper::addFlashMessage($e->getMessage(), 'error');
            
            // Re-render form with data
            LayoutHelper::setPageData(
                'Create User',
                'Add a new user to the platform',
                [
                    ['label' => 'Users', 'url' => Router::url('users')],
                    ['label' => 'Create User']
                ]
            );
            
            LayoutHelper::render('users/create', [
                'formData' => $_POST,
                'errors' => [$e->getMessage()]
            ]);
        }
    }
    
    // Show edit user form
    public static function edit($id)
    {
        AuthController::requireAuth();
        
        $controller = new self();
        $user = $controller->userModel->findById((int)$id);
        
        if (!$user) {
            LayoutHelper::addFlashMessage('User not found', 'error');
            redirect(Router::url('users'));
            return;
        }
        
        // Set page data
        LayoutHelper::setPageData(
            'Edit User',
            'Update user information',
            [
                ['label' => 'Users', 'url' => Router::url('users')],
                ['label' => $user['first_name'] . ' ' . $user['last_name'], 'url' => Router::url('users/' . $user['id'])],
                ['label' => 'Edit']
            ]
        );
        
        // Render view
        LayoutHelper::render('users/edit', [
            'user' => $user
        ]);
    }
    
    // Handle update user form submission
    public static function update($id)
    {
        AuthController::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(Router::url('users/' . $id . '/edit'));
            return;
        }
        
        $controller = new self();
        $user = $controller->userModel->findById((int)$id);
        
        if (!$user) {
            LayoutHelper::addFlashMessage('User not found', 'error');
            redirect(Router::url('users'));
            return;
        }
        
        try {
            // Validate CSRF token
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid security token');
            }
            
            // Validate input (excluding password for updates)
            $data = $controller->validateUserData($_POST, false, (int)$id);
            
            // Check if email already exists (excluding current user)
            $existingUser = $controller->userModel->findByEmail($data['email']);
            if ($existingUser && $existingUser['id'] != $id) {
                throw new Exception('Email address already exists');
            }
            
            // Check if username already exists (excluding current user)
            $existingUser = $controller->userModel->findByUsername($data['username']);
            if ($existingUser && $existingUser['id'] != $id) {
                throw new Exception('Username already exists');
            }
            
            // Update user
            $success = $controller->userModel->update((int)$id, $data);
            
            if ($success) {
                // Log activity
                $controller->logActivity('user_updated', (int)$id, [
                    'updated_fields' => array_keys($data)
                ]);
                
                LayoutHelper::addFlashMessage('User updated successfully', 'success');
                redirect(Router::url('users/' . $id));
            } else {
                throw new Exception('Failed to update user');
            }
            
        } catch (Exception $e) {
            LayoutHelper::addFlashMessage($e->getMessage(), 'error');
            redirect(Router::url('users/' . $id . '/edit'));
        }
    }
    
    // Delete user (soft delete)
    public static function delete($id)
    {
        AuthController::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(Router::url('users'));
            return;
        }
        
        $controller = new self();
        $user = $controller->userModel->findById((int)$id);
        
        if (!$user) {
            LayoutHelper::addFlashMessage('User not found', 'error');
            redirect(Router::url('users'));
            return;
        }
        
        // Prevent deleting yourself
        $currentUser = AuthController::getCurrentUser();
        if ($currentUser['id'] == $id) {
            LayoutHelper::addFlashMessage('You cannot delete your own account', 'error');
            redirect(Router::url('users'));
            return;
        }
        
        try {
            // Validate CSRF token
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid security token');
            }
            
            // Check if user has content that needs to be handled
            $contentStats = $controller->getUserContentStats((int)$id);
            if ($contentStats['total_content'] > 0) {
                throw new Exception('Cannot delete user with existing content. Please transfer or delete their content first.');
            }
            
            // Delete user
            $success = $controller->userModel->delete((int)$id);
            
            if ($success) {
                // Log activity
                $controller->logActivity('user_deleted', (int)$id, [
                    'deleted_user_email' => $user['email']
                ]);
                
                LayoutHelper::addFlashMessage('User deleted successfully', 'success');
            } else {
                throw new Exception('Failed to delete user');
            }
            
        } catch (Exception $e) {
            LayoutHelper::addFlashMessage($e->getMessage(), 'error');
        }
        
        redirect(Router::url('users'));
    }
    
    // Update user status via AJAX
    public static function updateStatus($id)
    {
        AuthController::requireAuth();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $controller = new self();
        $user = $controller->userModel->findById((int)$id);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $newStatus = $input['status'] ?? '';
            
            if (!in_array($newStatus, ['active', 'pending', 'suspended', 'banned'])) {
                throw new Exception('Invalid status');
            }
            
            // Prevent changing your own status
            $currentUser = AuthController::getCurrentUser();
            if ($currentUser['id'] == $id) {
                throw new Exception('You cannot change your own status');
            }
            
            $success = $controller->userModel->updateStatus((int)$id, $newStatus);
            
            if ($success) {
                // Log activity
                $controller->logActivity('user_status_changed', (int)$id, [
                    'old_status' => $user['status'],
                    'new_status' => $newStatus
                ]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'User status updated successfully',
                    'new_status' => $newStatus
                ]);
            } else {
                throw new Exception('Failed to update user status');
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Update user role via AJAX
    public static function updateRole($id)
    {
        AuthController::requireAuth();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $controller = new self();
        $user = $controller->userModel->findById((int)$id);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $newRole = $input['role'] ?? '';
            
            if (!in_array($newRole, ['public', 'contributor', 'super_admin'])) {
                throw new Exception('Invalid role');
            }
            
            // Prevent changing your own role
            $currentUser = AuthController::getCurrentUser();
            if ($currentUser['id'] == $id) {
                throw new Exception('You cannot change your own role');
            }
            
            $success = $controller->userModel->updateRole((int)$id, $newRole);
            
            if ($success) {
                // Log activity
                $controller->logActivity('user_role_changed', (int)$id, [
                    'old_role' => $user['role'],
                    'new_role' => $newRole
                ]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'User role updated successfully',
                    'new_role' => $newRole
                ]);
            } else {
                throw new Exception('Failed to update user role');
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Bulk actions for multiple users
    public static function bulkAction()
    {
        AuthController::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(Router::url('users'));
            return;
        }
        
        try {
            // Validate CSRF token
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid security token');
            }
            
            $action = $_POST['bulk_action'] ?? '';
            $userIds = $_POST['user_ids'] ?? [];
            
            if (empty($action) || empty($userIds)) {
                throw new Exception('Please select an action and users');
            }
            
            $controller = new self();
            $currentUser = AuthController::getCurrentUser();
            $processed = 0;
            
            foreach ($userIds as $userId) {
                $userId = (int)$userId;
                
                // Skip current user
                if ($userId === (int)$currentUser['id']) {
                    continue;
                }
                
                switch ($action) {
                    case 'activate':
                        if ($controller->userModel->updateStatus($userId, 'active')) {
                            $processed++;
                        }
                        break;
                        
                    case 'suspend':
                        if ($controller->userModel->updateStatus($userId, 'suspended')) {
                            $processed++;
                        }
                        break;
                        
                    case 'delete':
                        // Check if user has content
                        $stats = $controller->getUserContentStats($userId);
                        if ($stats['total_content'] == 0) {
                            if ($controller->userModel->delete($userId)) {
                                $processed++;
                            }
                        }
                        break;
                }
            }
            
            // Log activity
            $controller->logActivity('bulk_user_action', 0, [
                'action' => $action,
                'user_count' => $processed
            ]);
            
            LayoutHelper::addFlashMessage("Bulk action completed. {$processed} users processed.", 'success');
            
        } catch (Exception $e) {
            LayoutHelper::addFlashMessage($e->getMessage(), 'error');
        }
        
        redirect(Router::url('users'));
    }
    
    // Private helper methods
    private function validateUserData($data, $requirePassword = true, $excludeUserId = null)
    {
        $errors = [];
        $cleanData = [];
        
        // Username
        $username = trim($data['username'] ?? '');
        if (empty($username)) {
            $errors[] = 'Username is required';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be between 3 and 50 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, underscores, and hyphens';
        } else {
            $cleanData['username'] = $username;
        }
        
        // Email
        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } else {
            $cleanData['email'] = $email;
        }
        
        // Password (only required for new users)
        if ($requirePassword) {
            $password = $data['password'] ?? '';
            if (empty($password)) {
                $errors[] = 'Password is required';
            } elseif (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters long';
            } else {
                $cleanData['password'] = $password;
            }
            
            // Confirm password
            $confirmPassword = $data['confirm_password'] ?? '';
            if ($password !== $confirmPassword) {
                $errors[] = 'Password confirmation does not match';
            }
        } elseif (!empty($data['password'])) {
            // Optional password update
            $password = $data['password'];
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters long';
            } else {
                $confirmPassword = $data['confirm_password'] ?? '';
                if ($password !== $confirmPassword) {
                    $errors[] = 'Password confirmation does not match';
                } else {
                    $cleanData['password'] = $password;
                }
            }
        }
        
        // First name
        $firstName = trim($data['first_name'] ?? '');
        if (empty($firstName)) {
            $errors[] = 'First name is required';
        } elseif (strlen($firstName) > 100) {
            $errors[] = 'First name must not exceed 100 characters';
        } else {
            $cleanData['first_name'] = $firstName;
        }
        
        // Last name
        $lastName = trim($data['last_name'] ?? '');
        if (empty($lastName)) {
            $errors[] = 'Last name is required';
        } elseif (strlen($lastName) > 100) {
            $errors[] = 'Last name must not exceed 100 characters';
        } else {
            $cleanData['last_name'] = $lastName;
        }
        
        // Role
        $role = $data['role'] ?? 'public';
        if (!in_array($role, ['public', 'contributor', 'super_admin'])) {
            $errors[] = 'Invalid role selected';
        } else {
            $cleanData['role'] = $role;
        }
        
        // Status
        $status = $data['status'] ?? 'active';
        if (!in_array($status, ['pending', 'active', 'suspended', 'banned'])) {
            $errors[] = 'Invalid status selected';
        } else {
            $cleanData['status'] = $status;
        }
        
        // Optional fields
        if (!empty($data['bio'])) {
            if (strlen($data['bio']) > 1000) {
                $errors[] = 'Bio must not exceed 1000 characters';
            } else {
                $cleanData['bio'] = trim($data['bio']);
            }
        }
        
        if (!empty($data['phone'])) {
            $cleanData['phone'] = trim($data['phone']);
        }
        
        if (!empty($data['location'])) {
            $cleanData['location'] = trim($data['location']);
        }
        
        if (!empty($data['website'])) {
            if (!filter_var($data['website'], FILTER_VALIDATE_URL)) {
                $errors[] = 'Invalid website URL';
            } else {
                $cleanData['website'] = trim($data['website']);
            }
        }
        
        // Social media
        foreach (['social_twitter', 'social_facebook', 'social_linkedin'] as $social) {
            if (!empty($data[$social])) {
                $cleanData[$social] = trim($data[$social]);
            }
        }
        
        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }
        
        return $cleanData;
    }
    
    private function getUserContentStats($userId)
    {
        $db = Database::getInstance();
        
        try {
            $newsCount = $db->fetch("SELECT COUNT(*) as count FROM news WHERE author_id = ? AND deleted_at IS NULL", [$userId])['count'] ?? 0;
        } catch (Exception $e) {
            $newsCount = 0;
        }
        
        try {
            $eventsCount = $db->fetch("SELECT COUNT(*) as count FROM events WHERE organizer_id = ? AND deleted_at IS NULL", [$userId])['count'] ?? 0;
        } catch (Exception $e) {
            $eventsCount = 0;
        }
        
        try {
            $commentsCount = $db->fetch("SELECT COUNT(*) as count FROM comments WHERE user_id = ? AND deleted_at IS NULL", [$userId])['count'] ?? 0;
        } catch (Exception $e) {
            $commentsCount = 0;
        }
        
        return [
            'news_count' => (int)$newsCount,
            'events_count' => (int)$eventsCount,
            'comments_count' => (int)$commentsCount,
            'total_content' => (int)($newsCount + $eventsCount + $commentsCount)
        ];
    }
    
    private function getUserActivity($userId, $limit = 10)
    {
        $db = Database::getInstance();
        
        try {
            return $db->fetchAll("
                SELECT * FROM activity_logs 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ", [$userId, $limit]);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function logActivity($action, $targetId, $metadata = [])
    {
        $db = Database::getInstance();
        $currentUser = AuthController::getCurrentUser();
        
        try {
            $db->execute("
                INSERT INTO activity_logs (user_id, action, target_type, target_id, ip_address, user_agent, metadata, created_at) 
                VALUES (?, ?, 'user', ?, ?, ?, ?, NOW())
            ", [
                $currentUser['id'],
                $action,
                $targetId,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                json_encode($metadata)
            ]);
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }


    public static function getUserField($userId, $field = null)
    {
        AuthController::requireAuth();
        $controller = new self();
        $user = $controller->userModel->findById((int)$userId);
        
        // If no user found, return null
        if (!$user) {
            return null;
        }
        
        // If no specific field requested, return entire user array
        if ($field === null) {
            return $user;
        }
        
        // If specific field requested, return that field's value or null if field doesn't exist
        return isset($user[$field]) ? $user[$field] : null;
    }
}