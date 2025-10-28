<?php

    // admin/src/Views/auth/login.php - Login View

    // Prevent direct access
    if (!defined('ADMIN_ACCESS')) {
        die('Direct access not permitted');
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - NorthCity News & Events Platform</title>
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script>
        // include js configurations
        <?php echo file_get_contents(CONFIG_JS_FILE_PATH); ?>
    </script>
    
    <style>
        .login-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .login-form {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .form-input:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .login-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            animation: slideInDown 0.5s ease-out;
        }
        
        @keyframes slideInDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .form-container {
            animation: fadeInUp 0.8s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="login-container flex items-center justify-center p-4">
        <div class="form-container w-full max-w-md">
            <!-- Logo/Brand -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full shadow-lg mb-4">
                    <i class="fas fa-newspaper text-2xl text-indigo-600"></i>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">Admin Dashboard</h1>
                <p class="text-indigo-100">NorthCity News & Events Platform</p>
            </div>

            <!-- Error/Success Messages -->
            <?php if (!empty($error)): ?>
                <div class="alert bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div class="login-form rounded-2xl p-8 shadow-2xl">
                <form id="loginForm" method="POST" action="<?= Router::url('login') ?>" novalidate>
                    <!-- CSRF Token - Keep only this one -->
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="space-y-6">
                        <!-- Email Field -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-1"></i>
                                Email Address
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   required 
                                   autocomplete="email"
                                   class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-300"
                                   placeholder="Enter your email address"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            <div class="error-message text-red-500 text-sm mt-1 hidden" id="email-error"></div>
                        </div>

                        <!-- Password Field -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-lock mr-1"></i>
                                Password
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       required 
                                       autocomplete="current-password"
                                       class="form-input w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-300"
                                       placeholder="Enter your password">
                                <button type="button" 
                                        id="togglePassword" 
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                            <div class="error-message text-red-500 text-sm mt-1 hidden" id="password-error"></div>
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       id="remember_me" 
                                       name="remember_me" 
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                                    Remember me
                                </label>
                            </div>
                        </div>

                        <!-- Login Button -->
                        <div>
                            <button type="submit" 
                                    id="loginBtn"
                                    class="login-btn w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span id="loginBtnText">
                                    <i class="fas fa-sign-in-alt mr-2"></i>
                                    Sign In
                                </span>
                                <span id="loginBtnSpinner" class="hidden">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Signing In...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Additional Info -->
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500">
                        <i class="fas fa-shield-alt mr-1"></i>
                        This is a secure admin area. Only authorized personnel are allowed.
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8">
                <p class="text-sm text-indigo-100">
                    &copy; <?= date('Y') ?> NorthCity News & Events Platform. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <!-- Include login JavaScript -->
    <script src="<?= ADMIN_APP_URL ?>/assets/js/login.js"></script>
</body>
</html>
