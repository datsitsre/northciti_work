<?php

// api/src/Models/Category.php - Category Model

declare(strict_types=1);

namespace App\Models;

use App\Models\Base\Model;

class Category extends Model
{
    protected string $table = 'categories';
    
    protected array $fillable = [
        'name', 'slug', 'description', 'color', 'icon', 'parent_id', 'sort_order', 'is_active'
    ];
    
    protected array $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'parent_id' => 'integer'
    ];

    public function findByAny(string|int $identifier): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? OR slug = ? OR name = ? AND deleted_at IS NULL";
        $result = $this->db->fetch($sql, [$identifier, $identifier, $identifier]);
        return $result ? $this->castAttributes($result) : null;
    }

    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = ? AND deleted_at IS NULL";
        $result = $this->db->fetch($sql, [$slug]);
        return $result ? $this->castAttributes($result) : null;
    }

    public function findByName(string $name): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE name = ? AND deleted_at IS NULL";
        $result = $this->db->fetch($sql, [$name]);
        return $result ? $this->castAttributes($result) : null;
    }

    public function getActive(): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 AND deleted_at IS NULL 
                ORDER BY sort_order ASC, name ASC";
        
        $results = $this->db->fetchAll($sql);
        return array_map([$this, 'castAttributes'], $results);
    }

    public function getHierarchy(): array
    {
        $categories = $this->getActive();
        return $this->buildTree($categories);
    }

    public function getParentCategories(): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE parent_id IS NULL AND is_active = 1 AND deleted_at IS NULL 
                ORDER BY sort_order ASC, name ASC";
        
        $results = $this->db->fetchAll($sql);
        return array_map([$this, 'castAttributes'], $results);
    }

    public function getChildren(int $parentId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE parent_id = ? AND is_active = 1 AND deleted_at IS NULL 
                ORDER BY sort_order ASC, name ASC";
        
        $results = $this->db->fetchAll($sql, [$parentId]);
        return array_map([$this, 'castAttributes'], $results);
    }

    public function createCategory(array $data): ?array
    {
        // Generate slug if not provided
        if (!isset($data['slug']) && isset($data['name'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        // Ensure slug is unique
        if (isset($data['slug'])) {
            $data['slug'] = $this->ensureUniqueSlug($data['slug']);
        }

        return $this->create($data);
    }

    public function updateCategory(int $id, array $data): ?array
    {
        // Update slug if name changed
        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        // Ensure slug is unique (excluding current record)
        if (isset($data['slug'])) {
            $data['slug'] = $this->ensureUniqueSlug($data['slug'], $id);
        }

        return $this->update($id, $data);
    }

    public function getCategoryUsage(int $categoryId): array
    {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM news WHERE category_id = ? AND deleted_at IS NULL) as news_count,
                    (SELECT COUNT(*) FROM events WHERE category_id = ? AND deleted_at IS NULL) as events_count";
        
        return $this->db->fetch($sql, [$categoryId, $categoryId]);
    }

    public function getPopularCategories(int $limit = 10): array
    {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM news n WHERE n.category_id = c.id AND n.status = 'published' AND n.deleted_at IS NULL) +
                (SELECT COUNT(*) FROM events e WHERE e.category_id = c.id AND e.status = 'published' AND e.deleted_at IS NULL) as content_count
                FROM {$this->table} c
                WHERE c.is_active = 1 AND c.deleted_at IS NULL
                ORDER BY content_count DESC, c.name ASC
                LIMIT ?";
        
        $results = $this->db->fetchAll($sql, [$limit]);
        return array_map([$this, 'castAttributes'], $results);
    }

    public function searchCategories(string $query): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (name LIKE ? OR description LIKE ?) AND deleted_at IS NULL 
                ORDER BY is_active DESC, name ASC";
        
        $searchTerm = "%{$query}%";
        $results = $this->db->fetchAll($sql, [$searchTerm, $searchTerm]);
        return array_map([$this, 'castAttributes'], $results);
    }

    public function reorderCategories(array $categoryOrder): bool
    {
        $this->db->beginTransaction();
        
        try {
            foreach ($categoryOrder as $order => $categoryId) {
                $sql = "UPDATE {$this->table} SET sort_order = ? WHERE id = ?";
                $this->db->execute($sql, [$order + 1, $categoryId]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    private function buildTree(array $categories, int $parentId = null): array
    {
        $tree = [];
        
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $children = $this->buildTree($categories, (int)$category['id']);
                if (!empty($children)) {
                    $category['children'] = $children;
                }
                $tree[] = $category;
            }
        }
        
        return $tree;
    }

    private function generateSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }

    private function ensureUniqueSlug(string $slug, int $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    private function slugExists(string $slug, int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE slug = ? AND deleted_at IS NULL";
        $params = [$slug];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }

    public function canDelete(int $categoryId): bool
    {
        $usage = $this->getCategoryUsage($categoryId);
        return $usage['news_count'] == 0 && $usage['events_count'] == 0;
    }

    public function getStatistics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_categories,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_categories,
                    SUM(CASE WHEN parent_id IS NULL THEN 1 ELSE 0 END) as parent_categories,
                    SUM(CASE WHEN parent_id IS NOT NULL THEN 1 ELSE 0 END) as child_categories
                FROM {$this->table} 
                WHERE deleted_at IS NULL";
        
        return $this->db->fetch($sql);
    }
}