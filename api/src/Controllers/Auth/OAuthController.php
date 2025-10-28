<?php

// api/src/Controllers/Auth/OAuthController.php - OAuth Authentication Controller

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\JWTService;
use App\Services\EmailService;
use App\Services\OAuthService;
use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;

class OAuthController extends BaseController
{
    private User $userModel;
    private JWTService $jwtService;
    private EmailService $emailService;
    private OAuthService $oauthService;

    public function __construct(
        User $userModel,
        JWTService $jwtService,
        EmailService $emailService,
        OAuthService $oauthService
    ) {
        $this->userModel = $userModel;
        $this->jwtService = $jwtService;
        $this->emailService = $emailService;
        $this->oauthService = $oauthService;
        parent::__construct();
    }

    public function googleRedirect(Request $request): Response
    {
        $state = bin2hex(random_bytes(16));
        $redirectUri = $request->getQuery('redirect_uri') ?? $_ENV['FRONTEND_URL'] ?? $_ENV['APP_URL'];
        
        // Store state and redirect URI in session/cache for verification
        $this->storeOAuthState($state, $redirectUri);
        
        $authUrl = $this->oauthService->getGoogleAuthUrl($state);
        
        return $this->successResponse([
            'auth_url' => $authUrl,
            'state' => $state
        ]);
    }

    public function googleCallback(Request $request): Response
    {
        $data = $request->getData();
        $code = $data['code'] ?? $request->getQuery('code');
        $state = $data['state'] ?? $request->getQuery('state');
        
        if (!$code) {
            return $this->errorResponse('Authorization code is required');
        }

        if (!$state || !$this->verifyOAuthState($state)) {
            return $this->errorResponse('Invalid state parameter');
        }

        try {
            // Exchange code for access token
            $tokenResponse = $this->oauthService->exchangeGoogleCode($code);
            
            // Get user info from Google
            $googleUser = $this->oauthService->getGoogleUserInfo($tokenResponse['access_token']);
            
            // Find or create user
            $user = $this->findOrCreateOAuthUser('google', $googleUser, $tokenResponse);
            
            if (!$user) {
                return $this->errorResponse('Failed to authenticate user');
            }

            // Generate JWT tokens
            $tokens = $this->jwtService->generateTokens($user);
            
            // Update last login
            $this->userModel->updateLastLogin($user['id']);

            // Log activity
            $this->logActivity($user['id'], 'oauth_login', 'user', $user['id'], [
                'provider' => 'google',
                'provider_id' => $googleUser['id']
            ]);

            // Get redirect URI
            $redirectUri = $this->getStoredRedirectUri($state);

            return $this->successResponse([
                'user' => $user,
                'tokens' => $tokens,
                'redirect_uri' => $redirectUri
            ], 'Google authentication successful');

        } catch (\Exception $e) {
            return $this->errorResponse('Google authentication failed: ' . $e->getMessage());
        }
    }

    public function facebookRedirect(Request $request): Response
    {
        $state = bin2hex(random_bytes(16));
        $redirectUri = $request->getQuery('redirect_uri') ?? $_ENV['FRONTEND_URL'] ?? $_ENV['APP_URL'];
        
        $this->storeOAuthState($state, $redirectUri);
        
        $authUrl = $this->oauthService->getFacebookAuthUrl($state);
        
        return $this->successResponse([
            'auth_url' => $authUrl,
            'state' => $state
        ]);
    }

    public function facebookCallback(Request $request): Response
    {
        $data = $request->getData();
        $code = $data['code'] ?? $request->getQuery('code');
        $state = $data['state'] ?? $request->getQuery('state');
        
        if (!$code) {
            return $this->errorResponse('Authorization code is required');
        }

        if (!$state || !$this->verifyOAuthState($state)) {
            return $this->errorResponse('Invalid state parameter');
        }

        try {
            $tokenResponse = $this->oauthService->exchangeFacebookCode($code);
            $facebookUser = $this->oauthService->getFacebookUserInfo($tokenResponse['access_token']);
            
            $user = $this->findOrCreateOAuthUser('facebook', $facebookUser, $tokenResponse);
            
            if (!$user) {
                return $this->errorResponse('Failed to authenticate user');
            }

            $tokens = $this->jwtService->generateTokens($user);
            $this->userModel->updateLastLogin($user['id']);

            $this->logActivity($user['id'], 'oauth_login', 'user', $user['id'], [
                'provider' => 'facebook',
                'provider_id' => $facebookUser['id']
            ]);

            $redirectUri = $this->getStoredRedirectUri($state);

            return $this->successResponse([
                'user' => $user,
                'tokens' => $tokens,
                'redirect_uri' => $redirectUri
            ], 'Facebook authentication successful');

        } catch (\Exception $e) {
            return $this->errorResponse('Facebook authentication failed: ' . $e->getMessage());
        }
    }

    public function twitterRedirect(Request $request): Response
    {
        $state = bin2hex(random_bytes(16));
        $redirectUri = $request->getQuery('redirect_uri') ?? $_ENV['FRONTEND_URL'] ?? $_ENV['APP_URL'];
        
        $this->storeOAuthState($state, $redirectUri);
        
        try {
            $authData = $this->oauthService->getTwitterAuthUrl($state);
            
            return $this->successResponse([
                'auth_url' => $authData['auth_url'],
                'oauth_token' => $authData['oauth_token'],
                'state' => $state
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Twitter authentication setup failed: ' . $e->getMessage());
        }
    }

    public function twitterCallback(Request $request): Response
    {
        $data = $request->getData();
        $oauthToken = $data['oauth_token'] ?? $request->getQuery('oauth_token');
        $oauthVerifier = $data['oauth_verifier'] ?? $request->getQuery('oauth_verifier');
        $state = $data['state'] ?? $request->getQuery('state');
        
        if (!$oauthToken || !$oauthVerifier) {
            return $this->errorResponse('OAuth token and verifier are required');
        }

        if (!$state || !$this->verifyOAuthState($state)) {
            return $this->errorResponse('Invalid state parameter');
        }

        try {
            $tokenResponse = $this->oauthService->exchangeTwitterTokens($oauthToken, $oauthVerifier);
            $twitterUser = $this->oauthService->getTwitterUserInfo($tokenResponse['oauth_token'], $tokenResponse['oauth_token_secret']);
            
            $user = $this->findOrCreateOAuthUser('twitter', $twitterUser, $tokenResponse);
            
            if (!$user) {
                return $this->errorResponse('Failed to authenticate user');
            }

            $tokens = $this->jwtService->generateTokens($user);
            $this->userModel->updateLastLogin($user['id']);

            $this->logActivity($user['id'], 'oauth_login', 'user', $user['id'], [
                'provider' => 'twitter',
                'provider_id' => $twitterUser['id']
            ]);

            $redirectUri = $this->getStoredRedirectUri($state);

            return $this->successResponse([
                'user' => $user,
                'tokens' => $tokens,
                'redirect_uri' => $redirectUri
            ], 'Twitter authentication successful');

        } catch (\Exception $e) {
            return $this->errorResponse('Twitter authentication failed: ' . $e->getMessage());
        }
    }

    public function githubRedirect(Request $request): Response
    {
        $state = bin2hex(random_bytes(16));
        $redirectUri = $request->getQuery('redirect_uri') ?? $_ENV['FRONTEND_URL'] ?? $_ENV['APP_URL'];
        
        $this->storeOAuthState($state, $redirectUri);
        
        $authUrl = $this->oauthService->getGithubAuthUrl($state);
        
        return $this->successResponse([
            'auth_url' => $authUrl,
            'state' => $state
        ]);
    }

    public function githubCallback(Request $request): Response
    {
        $data = $request->getData();
        $code = $data['code'] ?? $request->getQuery('code');
        $state = $data['state'] ?? $request->getQuery('state');
        
        if (!$code) {
            return $this->errorResponse('Authorization code is required');
        }

        if (!$state || !$this->verifyOAuthState($state)) {
            return $this->errorResponse('Invalid state parameter');
        }

        try {
            $tokenResponse = $this->oauthService->exchangeGithubCode($code);
            $githubUser = $this->oauthService->getGithubUserInfo($tokenResponse['access_token']);
            
            $user = $this->findOrCreateOAuthUser('github', $githubUser, $tokenResponse);
            
            if (!$user) {
                return $this->errorResponse('Failed to authenticate user');
            }

            $tokens = $this->jwtService->generateTokens($user);
            $this->userModel->updateLastLogin($user['id']);

            $this->logActivity($user['id'], 'oauth_login', 'user', $user['id'], [
                'provider' => 'github',
                'provider_id' => $githubUser['id']
            ]);

            $redirectUri = $this->getStoredRedirectUri($state);

            return $this->successResponse([
                'user' => $user,
                'tokens' => $tokens,
                'redirect_uri' => $redirectUri
            ], 'GitHub authentication successful');

        } catch (\Exception $e) {
            return $this->errorResponse('GitHub authentication failed: ' . $e->getMessage());
        }
    }

    public function linkAccount(Request $request): Response
    {
        $data = $request->getData();
        $provider = $data['provider'];
        $code = $data['code'];
        
        if (!in_array($provider, ['google', 'facebook', 'twitter', 'github'])) {
            return $this->errorResponse('Invalid OAuth provider');
        }

        if (!$code) {
            return $this->errorResponse('Authorization code is required');
        }

        try {
            $userId = $request->user['id'];
            
            // Exchange code for token and get user info
            switch ($provider) {
                case 'google':
                    $tokenResponse = $this->oauthService->exchangeGoogleCode($code);
                    $providerUser = $this->oauthService->getGoogleUserInfo($tokenResponse['access_token']);
                    break;
                case 'facebook':
                    $tokenResponse = $this->oauthService->exchangeFacebookCode($code);
                    $providerUser = $this->oauthService->getFacebookUserInfo($tokenResponse['access_token']);
                    break;
                case 'github':
                    $tokenResponse = $this->oauthService->exchangeGithubCode($code);
                    $providerUser = $this->oauthService->getGithubUserInfo($tokenResponse['access_token']);
                    break;
                default:
                    throw new \Exception('Provider not implemented');
            }
            
            // Check if this provider account is already linked to another user
            $existingProvider = $this->getOAuthProvider($provider, $providerUser['id']);
            if ($existingProvider && $existingProvider['user_id'] !== $userId) {
                return $this->errorResponse('This account is already linked to another user');
            }

            // Link the account
            $success = $this->linkOAuthProvider($userId, $provider, $providerUser, $tokenResponse);
            
            if ($success) {
                $this->logActivity($userId, 'oauth_account_linked', 'user', $userId, [
                    'provider' => $provider,
                    'provider_id' => $providerUser['id']
                ]);

                return $this->successResponse([], ucfirst($provider) . ' account linked successfully');
            } else {
                return $this->errorResponse('Failed to link account');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Account linking failed: ' . $e->getMessage());
        }
    }

    public function unlinkAccount(Request $request): Response
    {
        $data = $request->getData();
        $provider = $data['provider'];
        
        if (!in_array($provider, ['google', 'facebook', 'twitter', 'github'])) {
            return $this->errorResponse('Invalid OAuth provider');
        }

        $userId = $request->user['id'];
        
        // Check if user has a password (can't unlink all OAuth if no password)
        $user = $this->userModel->find($userId);
        $hasPassword = !empty($user['password_hash']);
        
        // Count OAuth providers
        $oauthCount = $this->getUserOAuthProviderCount($userId);
        
        if (!$hasPassword && $oauthCount <= 1) {
            return $this->errorResponse('Cannot unlink the only authentication method. Set a password first.', [], 400);
        }

        $success = $this->unlinkOAuthProvider($userId, $provider);
        
        if ($success) {
            $this->logActivity($userId, 'oauth_account_unlinked', 'user', $userId, [
                'provider' => $provider
            ]);

            return $this->successResponse([], ucfirst($provider) . ' account unlinked successfully');
        } else {
            return $this->errorResponse('Failed to unlink account or account not found');
        }
    }

    public function getLinkedAccounts(Request $request): Response
    {
        $userId = $request->user['id'];
        
        $sql = "SELECT provider, provider_email, created_at FROM oauth_providers WHERE user_id = ? ORDER BY created_at DESC";
        $providers = $this->db->fetchAll($sql, [$userId]);
        
        return $this->successResponse([
            'linked_accounts' => $providers
        ]);
    }

    private function findOrCreateOAuthUser(string $provider, array $providerUser, array $tokenResponse): ?array
    {
        // Check if OAuth provider already exists
        $existingProvider = $this->getOAuthProvider($provider, $providerUser['id']);
        
        if ($existingProvider) {
            // Update token information
            $this->updateOAuthProvider($existingProvider['id'], $tokenResponse);
            
            // Return existing user
            return $this->userModel->find($existingProvider['user_id']);
        }

        // Check if user exists by email
        $email = $providerUser['email'] ?? null;
        $existingUser = null;
        
        if ($email) {
            $existingUser = $this->userModel->findByEmail($email);
        }

        if ($existingUser) {
            // Link OAuth provider to existing user
            $this->linkOAuthProvider($existingUser['id'], $provider, $providerUser, $tokenResponse);
            return $existingUser;
        }

        // Create new user
        if (!$email) {
            throw new \Exception('Email address is required from OAuth provider');
        }

        $userData = [
            'username' => $this->generateUniqueUsername($providerUser),
            'email' => $email,
            'first_name' => $providerUser['first_name'] ?? $providerUser['given_name'] ?? 'User',
            'last_name' => $providerUser['last_name'] ?? $providerUser['family_name'] ?? '',
            'profile_image' => $providerUser['picture'] ?? $providerUser['avatar_url'] ?? null,
            'role' => 'public',
            'status' => 'active',
            'email_verified' => true, // OAuth emails are considered verified
            'password' => '' // OAuth users don't need passwords initially
        ];

        $user = $this->userModel->createUser($userData);
        
        if ($user) {
            // Link OAuth provider
            $this->linkOAuthProvider($user['id'], $provider, $providerUser, $tokenResponse);
            
            // Send welcome email
            $this->emailService->sendWelcomeEmail($user);
            
            // Log activity
            $this->logActivity($user['id'], 'oauth_user_created', 'user', $user['id'], [
                'provider' => $provider,
                'provider_id' => $providerUser['id']
            ]);
        }

        return $user;
    }

    private function getOAuthProvider(string $provider, string $providerId): ?array
    {
        $sql = "SELECT * FROM oauth_providers WHERE provider = ? AND provider_id = ?";
        return $this->db->fetch($sql, [$provider, $providerId]);
    }

    private function linkOAuthProvider(int $userId, string $provider, array $providerUser, array $tokenResponse): bool
    {
        $sql = "INSERT INTO oauth_providers (user_id, provider, provider_id, provider_email, access_token, refresh_token, expires_at, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                access_token = VALUES(access_token),
                refresh_token = VALUES(refresh_token),
                expires_at = VALUES(expires_at),
                updated_at = NOW()";
        
        $expiresAt = null;
        if (isset($tokenResponse['expires_in'])) {
            $expiresAt = date('Y-m-d H:i:s', time() + $tokenResponse['expires_in']);
        }

        return $this->db->execute($sql, [
            $userId,
            $provider,
            $providerUser['id'],
            $providerUser['email'] ?? null,
            $tokenResponse['access_token'] ?? null,
            $tokenResponse['refresh_token'] ?? null,
            $expiresAt
        ]) > 0;
    }

    private function updateOAuthProvider(int $providerId, array $tokenResponse): bool
    {
        $sql = "UPDATE oauth_providers SET access_token = ?, refresh_token = ?, expires_at = ?, updated_at = NOW() WHERE id = ?";
        
        $expiresAt = null;
        if (isset($tokenResponse['expires_in'])) {
            $expiresAt = date('Y-m-d H:i:s', time() + $tokenResponse['expires_in']);
        }

        return $this->db->execute($sql, [
            $tokenResponse['access_token'] ?? null,
            $tokenResponse['refresh_token'] ?? null,
            $expiresAt,
            $providerId
        ]) > 0;
    }

    private function unlinkOAuthProvider(int $userId, string $provider): bool
    {
        $sql = "DELETE FROM oauth_providers WHERE user_id = ? AND provider = ?";
        return $this->db->execute($sql, [$userId, $provider]) > 0;
    }

    private function getUserOAuthProviderCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count FROM oauth_providers WHERE user_id = ?";
        return (int)$this->db->fetch($sql, [$userId])['count'];
    }

    private function generateUniqueUsername(array $providerUser): string
    {
        // Try different username strategies
        $baseUsername = $providerUser['login'] ?? 
                       $providerUser['username'] ?? 
                       strtolower(($providerUser['first_name'] ?? '') . ($providerUser['last_name'] ?? '')) ??
                       'user';
        
        // Clean username
        $baseUsername = preg_replace('/[^a-zA-Z0-9_-]/', '', $baseUsername);
        $baseUsername = substr($baseUsername, 0, 40); // Limit length
        
        if (empty($baseUsername)) {
            $baseUsername = 'user';
        }

        $username = $baseUsername;
        $counter = 1;
        
        while ($this->userModel->findByUsername($username)) {
            $username = $baseUsername . $counter;
            $counter++;
            
            if ($counter > 1000) { // Prevent infinite loop
                $username = $baseUsername . uniqid();
                break;
            }
        }
        
        return $username;
    }

    private function storeOAuthState(string $state, string $redirectUri): void
    {
        // Store in database or cache - using database for simplicity
        $sql = "INSERT INTO oauth_states (state, redirect_uri, expires_at, created_at) VALUES (?, ?, ?, NOW())";
        $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes
        $this->db->execute($sql, [$state, $redirectUri, $expiresAt]);
    }

    private function verifyOAuthState(string $state): bool
    {
        $sql = "SELECT id FROM oauth_states WHERE state = ? AND expires_at > NOW()";
        $result = $this->db->fetch($sql, [$state]);
        
        if ($result) {
            // Clean up used state
            $this->db->execute("DELETE FROM oauth_states WHERE id = ?", [$result['id']]);
            return true;
        }
        
        return false;
    }

    private function getStoredRedirectUri(string $state): ?string
    {
        $sql = "SELECT redirect_uri FROM oauth_states WHERE state = ?";
        $result = $this->db->fetch($sql, [$state]);
        return $result['redirect_uri'] ?? null;
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
