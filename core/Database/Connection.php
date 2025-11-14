<?php

namespace Core\Database;

use PDO;
use PDOException;

/**
 * Database Connection Class
 *
 * Manages PDO database connections with support for multiple
 * database drivers (MySQL, PostgreSQL, SQLite).
 */
class Connection
{
    /**
     * PDO instance
     */
    protected PDO $pdo;

    /**
     * Connection configuration
     */
    protected array $config;

    /**
     * Create a new database connection
     *
     * @param array $config
     * @throws PDOException
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Establish the database connection
     *
     * @return void
     * @throws PDOException
     */
    protected function connect(): void
    {
        $driver = $this->config['driver'] ?? 'mysql';

        try {
            $dsn = $this->buildDsn($driver);
            $username = $this->config['username'] ?? '';
            $password = $this->config['password'] ?? '';
            $options = $this->config['options'] ?? [];

            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Build the DSN string based on driver
     *
     * @param string $driver
     * @return string
     */
    protected function buildDsn(string $driver): string
    {
        return match ($driver) {
            'mysql' => $this->buildMysqlDsn(),
            'pgsql' => $this->buildPgsqlDsn(),
            'sqlite' => $this->buildSqliteDsn(),
            default => throw new \InvalidArgumentException("Unsupported database driver: {$driver}")
        };
    }

    /**
     * Build MySQL DSN
     *
     * @return string
     */
    protected function buildMysqlDsn(): string
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? '3306';
        $database = $this->config['database'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';

        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }

    /**
     * Build PostgreSQL DSN
     *
     * @return string
     */
    protected function buildPgsqlDsn(): string
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? '5432';
        $database = $this->config['database'] ?? '';

        return "pgsql:host={$host};port={$port};dbname={$database}";
    }

    /**
     * Build SQLite DSN
     *
     * @return string
     */
    protected function buildSqliteDsn(): string
    {
        $database = $this->config['database'] ?? ':memory:';

        return "sqlite:{$database}";
    }

    /**
     * Get the PDO instance
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a query and return the statement
     *
     * @param string $query
     * @param array $bindings
     * @return \PDOStatement
     */
    public function query(string $query, array $bindings = []): \PDOStatement
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);

        return $statement;
    }

    /**
     * Execute a query and fetch all results
     *
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function select(string $query, array $bindings = []): array
    {
        $statement = $this->query($query, $bindings);
        return $statement->fetchAll();
    }

    /**
     * Execute a query and fetch the first result
     *
     * @param string $query
     * @param array $bindings
     * @return mixed
     */
    public function selectOne(string $query, array $bindings = [])
    {
        $statement = $this->query($query, $bindings);
        return $statement->fetch();
    }

    /**
     * Execute an insert statement
     *
     * @param string $query
     * @param array $bindings
     * @return string Last insert ID
     */
    public function insert(string $query, array $bindings = []): string
    {
        $this->query($query, $bindings);
        return $this->pdo->lastInsertId();
    }

    /**
     * Execute an update statement
     *
     * @param string $query
     * @param array $bindings
     * @return int Number of affected rows
     */
    public function update(string $query, array $bindings = []): int
    {
        $statement = $this->query($query, $bindings);
        return $statement->rowCount();
    }

    /**
     * Execute a delete statement
     *
     * @param string $query
     * @param array $bindings
     * @return int Number of affected rows
     */
    public function delete(string $query, array $bindings = []): int
    {
        $statement = $this->query($query, $bindings);
        return $statement->rowCount();
    }

    /**
     * Begin a transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback a transaction
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Check if currently in a transaction
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Get the table prefix
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->config['prefix'] ?? '';
    }

    /**
     * Get the database name
     *
     * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->config['database'] ?? '';
    }
}
