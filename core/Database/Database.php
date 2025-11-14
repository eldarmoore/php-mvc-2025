<?php

namespace Core\Database;

/**
 * Database Manager Class
 *
 * Manages multiple database connections and provides a singleton
 * access point to database operations.
 */
class Database
{
    /**
     * The active database connections
     */
    protected static array $connections = [];

    /**
     * The default connection name
     */
    protected static string $defaultConnection = 'mysql';

    /**
     * Database configuration
     */
    protected static array $config = [];

    /**
     * Initialize the database manager
     *
     * @param array $config
     * @return void
     */
    public static function init(array $config): void
    {
        static::$config = $config;
        static::$defaultConnection = $config['default'] ?? 'mysql';
    }

    /**
     * Get a database connection
     *
     * @param string|null $name
     * @return Connection
     */
    public static function connection(?string $name = null): Connection
    {
        $name = $name ?? static::$defaultConnection;

        if (!isset(static::$connections[$name])) {
            static::$connections[$name] = static::createConnection($name);
        }

        return static::$connections[$name];
    }

    /**
     * Create a new connection instance
     *
     * @param string $name
     * @return Connection
     */
    protected static function createConnection(string $name): Connection
    {
        if (!isset(static::$config['connections'][$name])) {
            throw new \InvalidArgumentException("Database connection [{$name}] not configured");
        }

        $config = static::$config['connections'][$name];

        return new Connection($config);
    }

    /**
     * Execute a query and return all results
     *
     * @param string $query
     * @param array $bindings
     * @param string|null $connection
     * @return array
     */
    public static function select(string $query, array $bindings = [], ?string $connection = null): array
    {
        return static::connection($connection)->select($query, $bindings);
    }

    /**
     * Execute a query and return the first result
     *
     * @param string $query
     * @param array $bindings
     * @param string|null $connection
     * @return mixed
     */
    public static function selectOne(string $query, array $bindings = [], ?string $connection = null)
    {
        return static::connection($connection)->selectOne($query, $bindings);
    }

    /**
     * Execute an insert statement
     *
     * @param string $query
     * @param array $bindings
     * @param string|null $connection
     * @return string
     */
    public static function insert(string $query, array $bindings = [], ?string $connection = null): string
    {
        return static::connection($connection)->insert($query, $bindings);
    }

    /**
     * Execute an update statement
     *
     * @param string $query
     * @param array $bindings
     * @param string|null $connection
     * @return int
     */
    public static function update(string $query, array $bindings = [], ?string $connection = null): int
    {
        return static::connection($connection)->update($query, $bindings);
    }

    /**
     * Execute a delete statement
     *
     * @param string $query
     * @param array $bindings
     * @param string|null $connection
     * @return int
     */
    public static function delete(string $query, array $bindings = [], ?string $connection = null): int
    {
        return static::connection($connection)->delete($query, $bindings);
    }

    /**
     * Begin a transaction
     *
     * @param string|null $connection
     * @return bool
     */
    public static function beginTransaction(?string $connection = null): bool
    {
        return static::connection($connection)->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @param string|null $connection
     * @return bool
     */
    public static function commit(?string $connection = null): bool
    {
        return static::connection($connection)->commit();
    }

    /**
     * Rollback a transaction
     *
     * @param string|null $connection
     * @return bool
     */
    public static function rollback(?string $connection = null): bool
    {
        return static::connection($connection)->rollback();
    }

    /**
     * Run a transaction
     *
     * @param callable $callback
     * @param string|null $connection
     * @return mixed
     * @throws \Exception
     */
    public static function transaction(callable $callback, ?string $connection = null)
    {
        static::beginTransaction($connection);

        try {
            $result = $callback(static::connection($connection));
            static::commit($connection);

            return $result;
        } catch (\Exception $e) {
            static::rollback($connection);
            throw $e;
        }
    }

    /**
     * Create a new query builder instance
     *
     * @param string $table
     * @param string|null $connection
     * @return QueryBuilder
     */
    public static function table(string $table, ?string $connection = null): QueryBuilder
    {
        return new QueryBuilder(static::connection($connection), $table);
    }

    /**
     * Disconnect from a database
     *
     * @param string|null $name
     * @return void
     */
    public static function disconnect(?string $name = null): void
    {
        $name = $name ?? static::$defaultConnection;
        unset(static::$connections[$name]);
    }

    /**
     * Disconnect from all databases
     *
     * @return void
     */
    public static function disconnectAll(): void
    {
        static::$connections = [];
    }
}
