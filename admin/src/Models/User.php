<?php

// admin/src/Models/User.php - Enhanced User Model

class UserModel 
{
    private $db;
    
    public function __construct() 
    {
        $this->db = Database::getInstance();
    }
    
    public function findById(int $id): ?array 
    {
        $sql = "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL";
        return $this->db->fetch($sql, [$id]);
    }
    
    public function findByEmail(string $email): ?array 
    {
        $sql = "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL";
        $data = $this->db->fetch($sql, [$email]);
        return (!$data) ? [] : $data;
    }
    
    public function findByUsername(string $username): ?array 
    {
        $sql = "SELECT * FROM users WHERE username = ? AND deleted_at IS NULL";
        $data =  $this->db->fetch($sql, [$username]);
        return (!$data) ? [] : $data;
    }
    
    public function updateLastLogin(int $id): bool 
    {
        $sql = "UPDATE users SET last_login_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$id]) > 0;
    }

    public function updateRemember(int $id, bool $rememberMe): bool 
    {
        $sql = "UPDATE users SET remember_me = ? WHERE id = ?";
        return $this->db->execute($sql, [$rememberMe, $id]) > 0;
    }
    
    public function getAll(int $page = 1, int $perPage = 20): array 
    {
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $totalSql = "SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL";
        $total = $this->db->fetch($totalSql)['total'];
        
        // Get users
        $sql = "SELECT id, uuid, username, email, first_name, last_name, role, status, 
                       email_verified, created_at, last_login_at 
                FROM users 
                WHERE deleted_at IS NULL 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        $users = $this->db->fetchAll($sql, [$perPage, $offset]);
        
        return [
            'data' => $users,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => (int)$total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }
    
    public function getFiltered(array $filters, int $page = 1, int $perPage = 20): array 
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = ['deleted_at IS NULL'];
        $params = [];
        
        // Build where conditions
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $whereConditions[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        // Get total count
        $totalSql = "SELECT COUNT(*) as total FROM users {$whereClause}";
        $total = $this->db->fetch($totalSql, $params)['total'];
        
        // Get users
        $sql = "SELECT id, uuid, username, email, first_name, last_name, role, status, 
                       email_verified, created_at, last_login_at 
                FROM users 
                {$whereClause}
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        
        $users = $this->db->fetchAll($sql, $params);
        
        return [
            'data' => $users,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => (int)$total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }
    
    public function create(array $data): ?array 
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }
        
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO users (" . implode(', ', $fields) . ", created_at, updated_at) 
                VALUES (" . implode(', ', $placeholders) . ", NOW(), NOW())";
        
        $this->db->execute($sql, array_values($data));
        
        $id = $this->db->lastInsertId();
        return $this->findById((int)$id);
    }
    
    public function update(int $id, array $data): bool 
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }
        
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
        
        return $this->db->execute($sql, $params) > 0;
    }
    
    public function delete(int $id): bool 
    {
        $sql = "UPDATE users SET deleted_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$id]) > 0;
    }
    
    public function updateStatus(int $id, string $status): bool 
    {
        $validStatuses = ['pending', 'active', 'suspended', 'banned'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        $sql = "UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$status, $id]) > 0;
    }
    
    public function updateRole(int $id, string $role): bool 
    {
        $validRoles = ['public', 'contributor', 'super_admin'];
        if (!in_array($role, $validRoles)) {
            return false;
        }
        
        $sql = "UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$role, $id]) > 0;
    }
    
    public function search(string $query, int $page = 1, int $perPage = 20): array 
    {
        $offset = ($page - 1) * $perPage;
        $searchTerm = "%{$query}%";
        
        // Get total count
        $totalSql = "SELECT COUNT(*) as total FROM users 
                     WHERE (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?) 
                     AND deleted_at IS NULL";
        $total = $this->db->fetch($totalSql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm])['total'];
        
        // Get users
        $sql = "SELECT id, uuid, username, email, first_name, last_name, role, status, 
                       email_verified, created_at, last_login_at 
                FROM users 
                WHERE (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?) 
                AND deleted_at IS NULL 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        $users = $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $perPage, $offset]);
        
        return [
            'data' => $users,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => (int)$total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }
    
    public function getStatistics(): array 
    {
        $sql = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_users,
                    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_users,
                    SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) as banned_users,
                    SUM(CASE WHEN role = 'contributor' THEN 1 ELSE 0 END) as contributors,
                    SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users_month,
                    SUM(CASE WHEN last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as active_users_week
                FROM users 
                WHERE deleted_at IS NULL";
        
        return $this->db->fetch($sql) ?: [
            'total_users' => 0,
            'active_users' => 0,
            'pending_users' => 0,
            'suspended_users' => 0,
            'banned_users' => 0,
            'contributors' => 0,
            'admins' => 0,
            'new_users_month' => 0,
            'active_users_week' => 0
        ];
    }
}