<?php

// admin/src/Controllers/CommentsController.php - Admin Events Controller

declare(strict_types=1);

class CommentsController
{
    private $db;
    private $user;
    private $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->db = Database::getInstance();
        $this->user = AuthController::getCurrentUser();
        
        // Check admin authentication
        AuthController::requireAuth();
        $this->requireRoles(['super_admin']);
    }

    public function index()
    {
        // Check admin authentication
        AuthController::requireAuth();
        $this->requireRole('super_admin');
        
        // Load the events management view
        LayoutHelper::setPageData(
            'Comments Management',
            'Manage comments from events and news',
            [
                ['label' => 'Comments Management', 'url' => '/comments']
            ]
        );

        LayoutHelper::render('comments/index', [
            'title' => 'Comments Management',
            'user' => $this->user,
            'has_api_access' => ApiClient::isAuthenticated()
        ]);
    }


    // Helper methods
    protected function requireRole(string $role): void
    {
        if (!$this->user || $this->user['role'] !== $role) {
            throw new \Exception('Insufficient permissions');
        }
    }

    protected function requireRoles(array $allowedRoles): void
    {
        if (!$this->user || !in_array($this->user['role'], $allowedRoles)) {
            throw new \Exception('Insufficient permissions');
        }
    }


}