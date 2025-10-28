<?php
// api/src/Core/Database.php - Database Connection Handler with Transaction Support

declare(strict_types=1);

namespace App\Core;

class Database
{
    private \PDO $pdo;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    private function connect(): void
    {
        $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
        
        $this->pdo = new \PDO(
            $dsn,
            $this->config['username'],
            $this->config['password'],
            $this->config['options'] ?? []
        );
    }

    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollback();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Execute a callback within a database transaction
     * 
     * @param callable $callback Function to execute within transaction
     * @return mixed Result of the callback
     * @throws \Exception If transaction fails
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Create a raw SQL expression (for use in updates)
     * 
     * @param string $expression
     * @return RawExpression
     */
    public function raw(string $expression): RawExpression
    {
        return new RawExpression($expression);
    }
}

/**
 * Class to represent raw SQL expressions
 */
class RawExpression
{
    private string $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    public function __toString(): string
    {
        return $this->expression;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }
}