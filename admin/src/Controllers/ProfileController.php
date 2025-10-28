<?php
// admin/src/Controllers/ProfileController.php - Fixed Database Integration

class ProfileController {
    private $db;
    private $user;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->user = AuthController::getCurrentUser();
    }

    public static function index() {
        $controller = new self();
        
        if (!AuthController::isLoggedIn()) {
            redirect(Router::url('login'));
            return;
        }

        $user = $controller->user;
        
        // Set page data
        LayoutHelper::setPageData(
            'Profile Settings',
            'Manage your personal profile',
            [
                ['label' => 'Profile Settings']
            ]
        );
        
        // Render view
        LayoutHelper::render('profile/index', [
            'user' => $user
        ]);
    }

    public static function update() {
        $controller = new self();
        
        
        if (!AuthController::isLoggedIn()) {
            redirect(Router::url('login'));
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(Router::url('profile'));
            return;
        }

        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $errors = 'Invalid security token';
            $user = $controller->user;
            
            LayoutHelper::setPageData(
                'Profile Settings',
                'Manage your profile',
                [
                    ['label' => 'Profile Settings']
                ]
            );
            
            // Render view
            LayoutHelper::render('profile/index', [
                'user' => $user,
                'errors' => $errors
            ]);
        }

        $userId = $controller->user['id'];
        $updateData = [];
        $errors = [];
        $success = "";

        // Validate and prepare data
        if (!empty($_POST['first_name'])) {
            if (strlen($_POST['first_name']) > 100) {
                $errors['first_name'] = 'First name must not exceed 100 characters';
            } else {
                $updateData['first_name'] = trim($_POST['first_name']);
            }
        } else {
            $errors['first_name'] = 'First name is required';
        }

        if (!empty($_POST['last_name'])) {
            if (strlen($_POST['last_name']) > 100) {
                $errors['last_name'] = 'Last name must not exceed 100 characters';
            } else {
                $updateData['last_name'] = trim($_POST['last_name']);
            }
        } else {
            $errors['last_name'] = 'Last name is required';
        }

        if (!empty($_POST['email'])) {
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            } else {
                // Check if email exists for other users
                $existingUser = $controller->db->fetch("SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL", [$_POST['email'], $userId]);
                if ($existingUser) {
                    $errors['email'] = 'Email already exists';
                } else {
                    $updateData['email'] = trim($_POST['email']);
                }
            }
        } else {
            $errors['email'] = 'Email is required';
        }

        // Optional fields
        if (isset($_POST['bio'])) {
            if (strlen($_POST['bio']) > 1000) {
                $errors['bio'] = 'Bio must not exceed 1000 characters';
            } else {
                $updateData['bio'] = trim($_POST['bio']);
            }
        }

        if (isset($_POST['phone']) && !empty($_POST['phone'])) {
            if (!preg_match('/^[\+]?[1-9][\d]{0,15}$/', $_POST['phone'])) {
                $errors['phone'] = 'Invalid phone number format';
            } else {
                $updateData['phone'] = trim($_POST['phone']);
            }
        }

        // Handle password change
        if (!empty($_POST['current_password']) || !empty($_POST['new_password'])) {
            if (empty($_POST['current_password'])) {
                $errors['current_password'] = 'Current password is required';
            } elseif (empty($_POST['new_password'])) {
                $errors['new_password'] = 'New password is required';
            } elseif (strlen($_POST['new_password']) < 8) {
                $errors['new_password'] = 'New password must be at least 8 characters';
            } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
                $errors['confirm_password'] = 'Password confirmation does not match';
            } else {
                // Verify current password
                if (!password_verify($_POST['current_password'], $controller->user['password_hash'])) {
                    $errors['current_password'] = 'Current password is incorrect';
                } else {
                    $updateData['password_hash'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                }
            }
        }

        if (empty($errors)) {
            // Update user
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $setParts = [];
            $params = [];
            foreach ($updateData as $key => $value) {
                $setParts[] = "$key = ?";
                $params[] = $value;
            }
            $params[] = $userId;

            $sql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = ?";
            
            if ($controller->db->execute($sql, $params)) {
                // Log activity
                $controller->db->execute("INSERT INTO activity_logs (user_id, action, target_type, target_id, ip_address, user_agent, created_at) VALUES (?, 'profile_updated', 'user', ?, ?, ?, NOW())", [
                    $userId,
                    $userId,
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);

                $success = 'Profile updated successfully';
                // Update current user data in session
                $_SESSION['user'] = array_merge($_SESSION['user'], $updateData);
                $controller->user = $_SESSION['user'];
            } else {
                $error = 'Failed to update profile';
            }
        }

        $user = $controller->user;
        LayoutHelper::setPageData(
            'Profile Settings',
            'Manage your profile',
            [
                ['label' => 'Profile Settings']
            ]
        );

        // Render view
        LayoutHelper::render('profile/index', [
            'user' => $user,
            'errors' => $errors,
            'success' => $success
        ]);
    }
}

