<?php

// api/src/Models/Base/Repository.php - Base Repository Pattern

declare(strict_types=1);

namespace App\Models\Base;

use App\Core\Database;

abstract class Repository
{
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function search(string $query, array $columns, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        
        // Build search conditions
        $searchClauses = [];
        $params = [];
        
        foreach ($columns as $column) {
            $searchClauses[] = "{$column} LIKE ?";
            $params[] = "%{$query}%";
        }
        
        $whereClause = "WHERE (" . implode(" OR ", $searchClauses) . ")";
        
        // Add soft delete check if applicable
        if (property_exists($this, 'deletedAt') && $this->deletedAt) {
            $whereClause .= " AND {$this->deletedAt} IS NULL";
        }

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];

        // Get results
        $sql = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $results = $this->db->fetchAll($sql, $params);

        return [
            'data' => $results,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => (int)$total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_previous' => $page > 1
            ]
        ];
    }

    public function getByStatus(string $status, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        
        $whereClause = "WHERE status = ?";
        $params = [$status];
        
        if (property_exists($this, 'deletedAt') && $this->deletedAt) {
            $whereClause .= " AND {$this->deletedAt} IS NULL";
        }

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];

        // Get results
        $sql = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $results = $this->db->fetchAll($sql, $params);

        return [
            'data' => $results,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => (int)$total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_previous' => $page > 1
            ]
        ];
    }

    public function updateMultiple(array $ids, array $data): int
    {
        if (empty($ids) || empty($data)) {
            return 0;
        }

        $setClauses = [];
        foreach (array_keys($data) as $column) {
            $setClauses[] = "{$column} = ?";
        }

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE {$this->primaryKey} IN ({$placeholders})";
        
        $params = array_merge(array_values($data), $ids);
        
        return $this->db->execute($sql, $params);
    }
}