<?php

// api/src/Controllers/Api/CategoryController.php - Category API Controller

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\Category;
use App\Services\CacheService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\AuthorizationException;

class CategoryController extends BaseController
{
    private Category $categoryModel;
    private CacheService $cache;

    public function __construct(
        Database $database,      // Add Database as first parameter
        Category $categoryModel, 
        CacheService $cache)
    {
        parent::__construct();
        $this->db = $database;           // Initialize database property
        $this->categoryModel = $categoryModel;
        $this->cache = $cache;
    }

    public function index(Request $request): Response
    {
        $includeInactive = $request->getQuery('include_inactive') === 'true';
        $hierarchy = $request->getQuery('hierarchy') === 'true';
        
        $cacheKey = $this->cache->generateKey('categories', $includeInactive, $hierarchy);
        
        $categories = $this->cache->remember($cacheKey, function() use ($includeInactive, $hierarchy) {
            if ($hierarchy) {
                return $this->categoryModel->getHierarchy();
            } elseif ($includeInactive) {
                return $this->categoryModel->all();
            } else {
                return $this->categoryModel->getActive();
            }
        }, 1800); // Cache for 30 minutes

        return $this->response->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function statistics(Request $request): Response
    {
        // Only admin can view statistics
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $stats = $this->categoryModel->getStatistics();

        return $this->response->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    private function validateCategory(array $data, int $excludeId = null): array
    {
        $errors = [];

        // Name validation
        if (empty($data['name'])) {
            $errors['name'] = 'Category name is required';
        } elseif (strlen($data['name']) > 100) {
            $errors['name'] = 'Category name must not exceed 100 characters';
        }

        // Description validation
        if (isset($data['description']) && strlen($data['description']) > 1000) {
            $errors['description'] = 'Description must not exceed 1000 characters';
        }

        // Color validation
        if (isset($data['color']) && !empty($data['color'])) {
            if (!preg_match('/^#[a-fA-F0-9]{6}$/', $data['color'])) {
                $errors['color'] = 'Invalid color format (use #RRGGBB)';
            }
        }

        // Parent category validation
        if (isset($data['parent_id']) && !empty($data['parent_id'])) {
            $parent = $this->categoryModel->find((int)$data['parent_id']);
            if (!$parent) {
                $errors['parent_id'] = 'Parent category not found';
            } elseif ($excludeId && (int)$data['parent_id'] === $excludeId) {
                $errors['parent_id'] = 'Category cannot be its own parent';
            }
        }

        // Sort order validation
        if (isset($data['sort_order']) && !is_numeric($data['sort_order'])) {
            $errors['sort_order'] = 'Sort order must be a number';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    private function logActivity(int $userId, string $action, string $targetType, int $targetId): void
    {
        $sql = "INSERT INTO activity_logs (user_id, action, target_type, target_id, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $this->db->execute($sql, [
            $userId,
            $action,
            $targetType,
            $targetId,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $category = $this->categoryModel->find((int)$id);
        
        if (!$category) {
            throw new NotFoundException('Category not found');
        }

        // Get category usage statistics
        $usage = $this->categoryModel->getCategoryUsage((int)$id);
        
        // Get child categories
        $children = $this->categoryModel->getChildren((int)$id);

        $data = array_merge($category, [
            'usage' => $usage,
            'children' => $children
        ]);

        return $this->response->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function create(Request $request): Response
    {
        // Only admin can create categories
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $data = $request->getData();
        
        $validation = $this->validateCategory($data);
        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        $category = $this->categoryModel->createCategory($data);
        
        if (!$category) {
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to create category'
            ], 400);
        }

        // Clear category cache
        $this->cache->deletePattern('categories_%');

        // Log activity
        $this->logActivity($request->getUser()['id'], 'category_created', 'category', $category['id']);

        return $this->response->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    public function update(Request $request, string $id): Response
    {
        // Only admin can update categories
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $categoryId = (int)$id;
        $data = $request->getData();
        
        $existingCategory = $this->categoryModel->find($categoryId);
        if (!$existingCategory) {
            throw new NotFoundException('Category not found');
        }

        $validation = $this->validateCategory($data, $categoryId);
        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        $updatedCategory = $this->categoryModel->updateCategory($categoryId, $data);
        
        if (!$updatedCategory) {
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to update category'
            ], 400);
        }

        // Clear category cache
        $this->cache->deletePattern('categories_%');

        // Log activity
        $this->logActivity($request->getUser()['id'], 'category_updated', 'category', $categoryId);

        return $this->response->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $updatedCategory
        ]);
    }

    public function delete(Request $request, string $id): Response
    {
        // Only admin can delete categories
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $categoryId = (int)$id;
        
        $category = $this->categoryModel->find($categoryId);
        if (!$category) {
            throw new NotFoundException('Category not found');
        }

        // Check if category can be deleted (no content associated)
        if (!$this->categoryModel->canDelete($categoryId)) {
            return $this->response->json([
                'success' => false,
                'message' => 'Cannot delete category with associated content'
            ], 400);
        }

        $deleted = $this->categoryModel->delete($categoryId);
        
        if (!$deleted) {
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to delete category'
            ], 400);
        }

        // Clear category cache
        $this->cache->deletePattern('categories_%');

        // Log activity
        $this->logActivity($request->getUser()['id'], 'category_deleted', 'category', $categoryId);

        return $this->response->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    public function reorder(Request $request): Response
    {
        // Only admin can reorder categories
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $data = $request->getData();
        
        if (!isset($data['order']) || !is_array($data['order'])) {
            return $this->response->json([
                'success' => false,
                'message' => 'Order array is required'
            ], 400);
        }

        $success = $this->categoryModel->reorderCategories($data['order']);
        
        if (!$success) {
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to reorder categories'
            ], 400);
        }

        // Clear category cache
        $this->cache->deletePattern('categories_%');

        return $this->response->json([
            'success' => true,
            'message' => 'Categories reordered successfully'
        ]);
    }

    public function popular(Request $request): Response
    {
        $limit = min((int)($request->getQuery('limit') ?? 10), 50);
        
        $cacheKey = $this->cache->generateKey('categories_popular', $limit);
        
        $categories = $this->cache->remember($cacheKey, function() use ($limit) {
            return $this->categoryModel->getPopularCategories($limit);
        }, 3600); // Cache for 1 hour

        return $this->response->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function search(Request $request): Response
    {
        $query = $request->getQuery('q');
        
        if (empty($query)) {
            return $this->response->json([
                'success' => false,
                'message' => 'Search query is required'
            ], 400);
        }

        $categories = $this->categoryModel->searchCategories($query);

        return $this->response->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}

