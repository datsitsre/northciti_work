<?php

// api/routes/auth.php - Authentication Routes

use App\Core\Router;
use App\Controllers\Auth\AuthController;
use App\Controllers\Auth\OAuthController;
use App\Controllers\Auth\PasswordController;

function registerAuthRoutes(Router $router): void
{
    $router->group('/northcity/api/auth', function($router) {
        // Basic authentication
        $router->post('/login', AuthController::class . '@login');
        $router->post('/register', AuthController::class . '@register');
        $router->post('/logout', AuthController::class . '@logout');
        $router->post('/refresh', AuthController::class . '@refresh');
        // manage login token
        $router->post('/remember-token', AuthController::class . '@createRememberToken');
        $router->post('/remember-login', AuthController::class . '@rememberLogin');
        $router->delete('/remember-token', AuthController::class . '@deleteRememberToken');

        // Email verification
        $router->post('/verify-email', AuthController::class . '@verifyEmail');
        $router->post('/resend-verification', AuthController::class . '@resendVerification');
        
        // Password reset
        $router->post('/forgot-password', PasswordController::class . '@forgotPassword');
        $router->post('/reset-password', PasswordController::class . '@resetPassword');
        $router->post('/change-password', PasswordController::class . '@changePassword');
        
        // OAuth
        $router->get('/google', OAuthController::class . '@googleRedirect');
        $router->post('/google/callback', OAuthController::class . '@googleCallback');
        
        // Two-factor authentication
        $router->post('/2fa/enable', AuthController::class . '@enableTwoFactor');
        $router->post('/2fa/disable', AuthController::class . '@disableTwoFactor');
        $router->post('/2fa/verify', AuthController::class . '@verifyTwoFactor');
    });
}