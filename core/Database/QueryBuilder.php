<?php

namespace Core\Database;

/**
 * Query Builder Class
 *
 * Provides a fluent interface for building SQL queries.
 * Supports SELECT, INSERT, UPDATE, DELETE operations with
 * WHERE clauses, JOINs, ORDER BY, LIMIT, etc.
 */
class QueryBuilder
{
    /**
     * Database connection
     */
    protected Connection $connection;

    /**
     * The table name
     */
    protected string $table;

    /**
     * The columns to select
     */
    protected array $columns = ['*'];

    /**
     * WHERE clauses
     */
    protected array $wheres = [];

    /**
     * Bindings for WHERE clauses
     */
    protected array $bindings = [];

    /**
     * JOIN clauses
     */
    protected array $joins = [];

    /**
     * ORDER BY clauses
     */
    protected array $orders = [];

    /**
     * GROUP BY columns
     */
    protected array $groups = [];

    /**
     * HAVING clauses
     */
    protected array $havings = [];

    /**
     * LIMIT value
     */
    protected ?int $limit = null;

    /**
     * OFFSET value
     */
    protected ?int $offset = null;

    /**
     * Create a new query builder instance
     *
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Set the columns to select
     *
     * @param array|string $columns
     * @return $this
     */
    public function select($columns = ['*']): static
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Add a WHERE clause
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    public function where(string $column, string $operator, $value = null, string $boolean = 'AND'): static
    {
        // If only 2 arguments, assume operator is '='
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = compact('column', 'operator', 'value', 'boolean');
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Add an OR WHERE clause
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function orWhere(string $column, string $operator, $value = null): static
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add a WHERE IN clause
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereIn(string $column, array $values): static
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = [
            'type' => 'IN',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];

        foreach ($values as $value) {
            $this->bindings[] = $value;
        }

        return $this;
    }

    /**
     * Add a WHERE NULL clause
     *
     * @param string $column
     * @return $this
     */
    public function whereNull(string $column): static
    {
        $this->wheres[] = [
            'type' => 'NULL',
            'column' => $column,
            'boolean' => 'AND'
        ];

        return $this;
    }

    /**
     * Add a WHERE NOT NULL clause
     *
     * @param string $column
     * @return $this
     */
    public function whereNotNull(string $column): static
    {
        $this->wheres[] = [
            'type' => 'NOT NULL',
            'column' => $column,
            'boolean' => 'AND'
        ];

        return $this;
    }

    /**
     * Add a JOIN clause
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @param string $type
     * @return $this
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $this->joins[] = compact('table', 'first', 'operator', 'second', 'type');
        return $this;
    }

    /**
     * Add a LEFT JOIN clause
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return $this
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add a RIGHT JOIN clause
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return $this
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Add an ORDER BY clause
     *
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orders[] = ['column' => $column, 'direction' => strtoupper($direction)];
        return $this;
    }

    /**
     * Add a GROUP BY clause
     *
     * @param string|array $columns
     * @return $this
     */
    public function groupBy($columns): static
    {
        $this->groups = array_merge($this->groups, is_array($columns) ? $columns : func_get_args());
        return $this;
    }

    /**
     * Set the LIMIT
     *
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the OFFSET
     *
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Alias for limit/offset pagination
     *
     * @param int $page
     * @param int $perPage
     * @return $this
     */
    public function paginate(int $page = 1, int $perPage = 15): static
    {
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        return $this;
    }

    /**
     * Execute the query and get all results
     *
     * @return array
     */
    public function get(): array
    {
        $sql = $this->toSql();
        return $this->connection->select($sql, $this->bindings);
    }

    /**
     * Execute the query and get the first result
     *
     * @return mixed
     */
    public function first()
    {
        $this->limit(1);
        $sql = $this->toSql();
        return $this->connection->selectOne($sql, $this->bindings);
    }

    /**
     * Find a record by ID
     *
     * @param mixed $id
     * @param string $column
     * @return mixed
     */
    public function find($id, string $column = 'id')
    {
        return $this->where($column, '=', $id)->first();
    }

    /**
     * Get the count of results
     *
     * @param string $column
     * @return int
     */
    public function count(string $column = '*'): int
    {
        $this->columns = ["COUNT({$column}) as count"];
        $result = $this->first();
        return (int) ($result->count ?? 0);
    }

    /**
     * Insert a new record
     *
     * @param array $data
     * @return string Last insert ID
     */
    public function insert(array $data): string
    {
        $columns = array_keys($data);
        $values = array_values($data);

        $columnsString = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $sql = "INSERT INTO {$this->table} ({$columnsString}) VALUES ({$placeholders})";

        return $this->connection->insert($sql, $values);
    }

    /**
     * Update records
     *
     * @param array $data
     * @return int Number of affected rows
     */
    public function update(array $data): int
    {
        $sets = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $bindings[] = $value;
        }

        $setsString = implode(', ', $sets);
        $sql = "UPDATE {$this->table} SET {$setsString}";

        if (!empty($this->wheres)) {
            $sql .= ' ' . $this->buildWheres();
            $bindings = array_merge($bindings, $this->bindings);
        }

        return $this->connection->update($sql, $bindings);
    }

    /**
     * Delete records
     *
     * @return int Number of affected rows
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= ' ' . $this->buildWheres();
        }

        return $this->connection->delete($sql, $this->bindings);
    }

    /**
     * Build the SQL query
     *
     * @return string
     */
    public function toSql(): string
    {
        $columns = implode(', ', $this->columns);
        $sql = "SELECT {$columns} FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= ' ' . $this->buildJoins();
        }

        if (!empty($this->wheres)) {
            $sql .= ' ' . $this->buildWheres();
        }

        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        if (!empty($this->orders)) {
            $sql .= ' ' . $this->buildOrders();
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Build WHERE clauses
     *
     * @return string
     */
    protected function buildWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $sql = 'WHERE ';
        $clauses = [];

        foreach ($this->wheres as $index => $where) {
            $boolean = $index === 0 ? '' : $where['boolean'] . ' ';

            if (isset($where['type'])) {
                if ($where['type'] === 'IN') {
                    $placeholders = implode(',', array_fill(0, count($where['values']), '?'));
                    $clauses[] = $boolean . "{$where['column']} IN ({$placeholders})";
                } elseif ($where['type'] === 'NULL') {
                    $clauses[] = $boolean . "{$where['column']} IS NULL";
                } elseif ($where['type'] === 'NOT NULL') {
                    $clauses[] = $boolean . "{$where['column']} IS NOT NULL";
                }
            } else {
                $clauses[] = $boolean . "{$where['column']} {$where['operator']} ?";
            }
        }

        return $sql . implode(' ', $clauses);
    }

    /**
     * Build JOIN clauses
     *
     * @return string
     */
    protected function buildJoins(): string
    {
        $sql = '';

        foreach ($this->joins as $join) {
            $sql .= "{$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']} ";
        }

        return trim($sql);
    }

    /**
     * Build ORDER BY clauses
     *
     * @return string
     */
    protected function buildOrders(): string
    {
        $orders = [];

        foreach ($this->orders as $order) {
            $orders[] = "{$order['column']} {$order['direction']}";
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

    /**
     * Get the bindings
     *
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}
