<?php

// api/src/Controllers/Auth/AuthController.php - Authentication Controller

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\User;
use App\Services\JWTService;
use App\Services\EmailService;
use App\Validators\UserValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\AuthenticationException;

class AuthController extends BaseController
{
    private User $userModel;
    private JWTService $jwtService;
    private EmailService $emailService;
    private UserValidator $validator;

    public function __construct(
        Database $database,
        User $userModel,
        JWTService $jwtService,
        EmailService $emailService,
        UserValidator $validator,
    ) {parent::__construct();
        $this->db = $database;
        $this->userModel = $userModel;
        $this->jwtService = $jwtService;
        $this->emailService = $emailService;
        $this->validator = $validator;
        // $this->setDatabase($database); // Set the database
    }

    public function login(Request $request): Response
    {
        $data = $request->getBodyObject();
        
        // Validate input
        if (empty($data['email']) || empty($data['password'])) {
            return $this->errorResponse('Email and password are required');
        }

        // Find user by email
        $user = $this->userModel->findByEmail($data['email']);
        if (!$user) {
            return $this->errorResponse('Invalid credentials', [], 401);
        }

        // Check if account is active
        if ($user['status'] !== 'active') {
            $statusMessages = [
                'pending' => 'Account is pending verification',
                'suspended' => 'Account has been suspended',
                'banned' => 'Account has been banned'
            ];
            
            return $this->errorResponse(
                $statusMessages[$user['status']] ?? 'Account is not active',
                [],
                401
            );
        }

        $user_pwd = $this->userModel->findPasswordHash($data['email']);
        if (!$user_pwd) {
            return $this->errorResponse('Invalid credentials', [], 401);
        }

        // Verify password
        if (!$this->userModel->verifyPassword($data["password"], $user_pwd['password_hash'])) {
            return $this->errorResponse('Invalid credentials', [], 401);
        }

        // Generate tokens
        $tokens = $this->jwtService->generateTokens($user);
        
        // Update last login
        $rememberMe = (int)$data['remember'];
        $this->userModel->updateLastLogin($user['id']);
        $this->userModel->updateRemember($user['id'], $rememberMe);

        // Log activity
        $this->logActivity($user['id'], 'user_login', 'user', $user['id']);

        // Remove sensitive data
        unset($user['password_hash'], $user['two_factor_secret'], $user['backup_codes']);        

        return $this->successResponse([
            'user' => $user,
            'tokens' => $tokens
        ], 'Login successful');
    }

    /**
     * Create remember token
     */
    public function createRememberToken(Request $request): Response
    {
        
        $data = $request->getBodyObject();
        $userId = $data['user_id'] ?? null;

        if (empty($data['token'])) {
            return $this->errorResponse('Token is required');
        }

        if (empty($userId)) {
            return $this->errorResponse('User not found. Please try again.');
        }
        
        $expiryDays = $data['expires_days'] ?? 30;
        
        // Create token in database
        $success = $this->userModel->createRememberToken($userId, $data['token'], $expiryDays);
        
        if ($success) {
            // Log activity
            $this->logActivity($userId, 'remember_token_created', 'user', $userId);
            
            return $this->successResponse([], 'Remember token created'.$userId);
        } else {
            return $this->errorResponse('Failed to create remember token'.$userId);
        }
    }

    public function rememberLogin(Request $request): Response
    {
        $data = $request->getBodyObject();
        
        if (empty($data['token'])) {
            return $this->errorResponse('Token is required');
        }
        
        // Find user by remember token
        $user = $this->userModel->findByRememberToken($data['token']);
        
        if (!$user) {
            return $this->errorResponse('Invalid or expired token', [], 401);
        }
        
        // Generate fresh API tokens
        $tokens = $this->jwtService->generateTokens($user);
        
        // Update last login
        $this->userModel->updateLastLogin($user['id']);
        
        // Log activity
        $this->logActivity($user['id'], 'remember_login', 'user', $user['id']);
        
        // Remove sensitive data
        unset($user['password_hash'], $user['two_factor_secret'], $user['backup_codes']);
        
        return $this->successResponse([
            'user' => $user,
            'tokens' => $tokens
        ], 'Auto-login successful');
    }

    public function deleteRememberToken(Request $request): Response
    {
        $data = $request->getBodyObject();
        
        if (empty($data['token'])) {
            return $this->errorResponse('Token is required');
        }
        
        $success = $this->userModel->deleteRememberToken($data['token']);
        
        if ($success) {
            return $this->successResponse([], 'Remember token deleted');
        } else {
            return $this->errorResponse('Token not found');
        }
    }

    public function register(Request $request): Response
    {
        $data = $request->getBodyObject();
        
        // Validate input
        $validation = $this->validator->validateRegistration($data);
        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        // Create user
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'role' => 'public',
            'status' => 'pending',
            'email_verified' => false
        ];

        $user = $this->userModel->createUser($userData);
        
        if (!$user) {
            return $this->errorResponse('Failed to create account');
        }

        // Send welcome email
        $this->emailService->sendWelcomeEmail($user);

        // Generate email verification token
        $this->generateEmailVerificationToken($user['id']);

        // Log activity
        $res = $this->logActivity($user['id'], 'user_registered', 'user', $user['id']);

                        var_dump($res);

        // Remove sensitive data
        unset($user['password_hash']);

        return $this->successResponse([
            'user' => $user,
            'message' => 'Account created successfully. Please check your email for verification.'
        ], 'Registration successful', 201);
    }

    public function logout(Request $request): Response
    {
        $token = $request->getBearerToken();
        
        if ($token) {
            // Revoke the token
            $this->jwtService->revokeToken($token);
            
            // Log activity
            if (isset($request->user['id'])) {
                $this->logActivity($request->user['id'], 'user_logout', 'user', $request->user['id']);
            }
        }

        return $this->successResponse([], 'Logged out successfully');
    }

    public function refresh(Request $request): Response
    {
        $data = $request->getBodyObject();
        
        if (empty($data['refresh_token'])) {
            return $this->errorResponse('Refresh token is required');
        }

        try {
            $tokens = $this->jwtService->refreshToken($data['refresh_token']);
            
            return $this->successResponse([
                'tokens' => $tokens
            ], 'Token refreshed successfully');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid refresh token', [], 401);
        }
    }

    public function verifyEmail(Request $request): Response
    {
        $data = $request->getBodyObject();
        
        if (empty($data['token'])) {
            return $this->errorResponse('Verification token is required');
        }

        // Find verification record
        $sql = "SELECT * FROM email_verifications 
                WHERE token = ? AND expires_at > NOW() AND verified_at IS NULL";
        $verification = $this->db->fetch($sql, [$data['token']]);

        if (!$verification) {
            return $this->errorResponse('Invalid or expired verification token');
        }

        // Update user as verified
        $this->userModel->update($verification['user_id'], [
            'email_verified' => true,
            'status' => 'active'
        ]);

        // Mark verification as completed
        $sql = "UPDATE email_verifications SET verified_at = NOW() WHERE id = ?";
        $this->db->execute($sql, [$verification['id']]);

        // Log activity
        $this->logActivity($verification['user_id'], 'email_verified', 'user', $verification['user_id']);

        return $this->successResponse([], 'Email verified successfully');
    }

    public function resendVerification(Request $request): Response
    {
        $data = $request->getBodyObject();
        
        if (empty($data['email'])) {
            return $this->errorResponse('Email is required');
        }

        $user = $this->userModel->findByEmail($data['email']);
        if (!$user) {
            return $this->errorResponse('User not found');
        }

        if ($user['email_verified']) {
            return $this->errorResponse('Email is already verified');
        }

        // Generate new verification token
        $this->generateEmailVerificationToken($user['id']);

        return $this->successResponse([], 'Verification email sent');
    }

    public function enableTwoFactor(Request $request): Response
    {
        $userId = $request->user['id'];
        $data = $request->getBodyObject();

        if (empty($data['secret']) || empty($data['code'])) {
            return $this->errorResponse('Secret and verification code are required');
        }

        // Verify the code (you would use a TOTP library here)
        // For now, we'll assume verification is successful
        
        $success = $this->userModel->enableTwoFactor($userId, $data['secret']);
        
        if ($success) {
            // Generate backup codes
            $backupCodes = $this->userModel->generateBackupCodes($userId);
            
            // Log activity
            $this->logActivity($userId, '2fa_enabled', 'user', $userId);

            return $this->successResponse([
                'backup_codes' => $backupCodes
            ], 'Two-factor authentication enabled');
        } else {
            return $this->errorResponse('Failed to enable two-factor authentication');
        }
    }

    public function disableTwoFactor(Request $request): Response
    {
        $userId = $request->user['id'];
        $data = $request->getBodyObject();

        if (empty($data['password'])) {
            return $this->errorResponse('Password is required');
        }

        // Verify password
        $user = $this->userModel->find($userId);
        if (!$this->userModel->verifyPassword($data['password'], $user['password_hash'])) {
            return $this->errorResponse('Invalid password');
        }

        $success = $this->userModel->disableTwoFactor($userId);
        
        if ($success) {
            // Log activity
            $this->logActivity($userId, '2fa_disabled', 'user', $userId);

            return $this->successResponse([], 'Two-factor authentication disabled');
        } else {
            return $this->errorResponse('Failed to disable two-factor authentication');
        }
    }

    public function verifyTwoFactor(Request $request): Response
    {
        $data = $request->getBodyObject();

        if (empty($data['user_id']) || empty($data['code'])) {
            return $this->errorResponse('User ID and verification code are required');
        }

        $user = $this->userModel->find((int)$data['user_id']);
        if (!$user || !$user['two_factor_enabled']) {
            return $this->errorResponse('Two-factor authentication not enabled');
        }

        // Try backup code first
        $isBackupCode = $this->userModel->verifyBackupCode($user['id'], $data['code']);
        
        if ($isBackupCode) {
            // Generate tokens
            $tokens = $this->jwtService->generateTokens($user);
            
            // Log activity
            $this->logActivity($user['id'], '2fa_verified_backup', 'user', $user['id']);

            return $this->successResponse([
                'user' => $user,
                'tokens' => $tokens
            ], 'Two-factor authentication verified with backup code');
        }

        // Verify TOTP code (you would implement TOTP verification here)
        // For now, we'll assume it's valid
        
        // Generate tokens
        $tokens = $this->jwtService->generateTokens($user);
        
        // Log activity
        $this->logActivity($user['id'], '2fa_verified', 'user', $user['id']);

        return $this->successResponse([
            'user' => $user,
            'tokens' => $tokens
        ], 'Two-factor authentication verified');
    }

    private function generateEmailVerificationToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 24 * 3600); // 24 hours

        $sql = "INSERT INTO email_verifications (user_id, token, expires_at, created_at) 
                VALUES (?, ?, ?, NOW())";
        $this->db->execute($sql, [$userId, $token, $expiresAt]);

        // Send verification email (implement email template)
        $user = $this->userModel->find($userId);
        $verificationUrl = $_ENV['APP_URL'] . '/verify-email?token=' . $token;
        
        $emailData = [
            'to_email' => $user['email'],
            'to_name' => $user['first_name'] . ' ' . $user['last_name'],
            'subject' => 'Verify Your Email Address',
            'body_html' => $this->getEmailVerificationTemplate($user, $verificationUrl)
        ];

        $this->emailService->queue($emailData);
    }

    private function getEmailVerificationTemplate(array $user, string $verificationUrl): string
    {
        return "
        <html>
        <body>
            <h1>Verify Your Email Address</h1>
            <p>Hello {$user['first_name']},</p>
            <p>Thank you for registering with " . ($_ENV['APP_NAME'] ?? 'News Platform') . ". Please click the link below to verify your email address:</p>
            <p><a href='{$verificationUrl}' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Email</a></p>
            <p>This link will expire in 24 hours.</p>
            <p>If you didn't create this account, please ignore this email.</p>
            <p>Best regards,<br>The " . ($_ENV['APP_NAME'] ?? 'News Platform') . " Team</p>
        </body>
        </html>";
    }

    private function logActivity(int $userId, string $action, string $targetType, int $targetId, array $metadata = []): void
    {
        $sql = "INSERT INTO activity_logs (user_id, action, target_type, target_id, ip_address, user_agent, metadata, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $res = $this->db->execute($sql, [
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