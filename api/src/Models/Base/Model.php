<?php

// api/src/Models/Base/Model.php - Base Model Class

declare(strict_types=1);

namespace App\Models\Base;

use App\Core\Database;

abstract class Model
{
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected bool $timestamps = true;
    protected ?string $deletedAt = 'deleted_at'; // null to disable soft deletes

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        if ($this->deletedAt) {
            $sql .= " AND {$this->deletedAt} IS NULL";
        }
        
        $result = $this->db->fetch($sql, [$id]);
        return $result ? $this->castAttributes($result) : null;
    }

    public function findByUuid(string $uuid): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE uuid = ?";
        if ($this->deletedAt) {
            $sql .= " AND {$this->deletedAt} IS NULL";
        }
        
        $result = $this->db->fetch($sql, [$uuid]);
        return $result ? $this->castAttributes($result) : null;
    }

    public function findWhere(array $conditions, string $operator = 'AND'): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE ";
        $params = [];
        $whereClauses = [];

        foreach ($conditions as $column => $value) {
            $whereClauses[] = "{$column} = ?";
            $params[] = $value;
        }

        $sql .= implode(" {$operator} ", $whereClauses);
        
        if ($this->deletedAt) {
            $sql .= " AND {$this->deletedAt} IS NULL";
        }

        $results = $this->db->fetchAll($sql, $params);
        return array_map([$this, 'castAttributes'], $results);
    }

    public function all(array $columns = ['*']): array
    {
        $columnStr = implode(', ', $columns);
        $sql = "SELECT {$columnStr} FROM {$this->table}";
        
        if ($this->deletedAt) {
            $sql .= " WHERE {$this->deletedAt} IS NULL";
        }
        
        $sql .= " ORDER BY created_at DESC";

        $results = $this->db->fetchAll($sql);
        return array_map([$this, 'castAttributes'], $results);
    }

    public function paginate(int $page = 1, int $perPage = 10, array $conditions = []): array
    {
        $offset = ($page - 1) * $perPage;
        
        // Build WHERE clause
        $whereClause = '';
        $params = [];
        
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "{$column} = ?";
                $params[] = $value;
            }
            $whereClause = "WHERE " . implode(" AND ", $whereClauses);
        }
        
        if ($this->deletedAt) {
            $deleteClause = "{$this->deletedAt} IS NULL";
            $whereClause = $whereClause ? 
                $whereClause . " AND {$deleteClause}" : 
                "WHERE {$deleteClause}";
        }

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];

        // Get paginated results
        $sql = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $results = $this->db->fetchAll($sql, $params);

        return [
            'data' => array_map([$this, 'castAttributes'], $results),
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

    public function create(array $data): ?array
    {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // Add UUID if column exists
        if (!isset($data['uuid']) && $this->hasUuidColumn()) {
            $data['uuid'] = $this->generateUuid();
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->db->query($sql, array_values($data));
        $id = $this->db->lastInsertId();
        
        return $this->find((int)$id);
    }

    public function update(int $id, array $data): ?array
    {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $setClauses = [];
        foreach (array_keys($data) as $column) {
            $setClauses[] = "{$column} = ?";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE {$this->primaryKey} = ?";
        $params = array_merge(array_values($data), [$id]);
        
        $this->db->query($sql, $params);
        
        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        if ($this->deletedAt) {
            // Soft delete
            $sql = "UPDATE {$this->table} SET {$this->deletedAt} = ? WHERE {$this->primaryKey} = ?";
            return $this->db->execute($sql, [date('Y-m-d H:i:s'), $id]) > 0;
        } else {
            // Hard delete
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
            return $this->db->execute($sql, [$id]) > 0;
        }
    }

    public function restore(int $id): bool
    {
        if (!$this->deletedAt) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET {$this->deletedAt} = NULL WHERE {$this->primaryKey} = ?";
        return $this->db->execute($sql, [$id]) > 0;
    }

    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function castAttributes(array $attributes): array
    {
        foreach ($this->casts as $key => $type) {
            if (isset($attributes[$key])) {
                $attributes[$key] = $this->castAttribute($attributes[$key], $type);
            }
        }

        // Remove hidden attributes
        foreach ($this->hidden as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }

    protected function castAttribute($value, string $type)
    {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'float':
            case 'double':
                return (float)$value;
            case 'bool':
            case 'boolean':
                return (bool)$value;
            case 'array':
            case 'json':
                return json_decode($value, true);
            case 'object':
                return json_decode($value);
            case 'datetime':
                return new \DateTime($value);
            default:
                return $value;
        }
    }

    protected function hasUuidColumn(): bool
    {
        $sql = "SHOW COLUMNS FROM {$this->table} LIKE 'uuid'";
        return $this->db->fetch($sql) !== null;
    }

    protected function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function logActivity(int $userId, string $action, string $targetType, int $targetId, array $metadata = []): void
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
