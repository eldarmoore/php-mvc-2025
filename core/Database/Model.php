<?php

namespace Core\Database;

/**
 * Base Model Class
 *
 * Provides ORM functionality with Active Record pattern.
 * All application models should extend this class.
 */
abstract class Model
{
    /**
     * The table associated with the model
     */
    protected string $table;

    /**
     * The primary key for the model
     */
    protected string $primaryKey = 'id';

    /**
     * The connection name for the model
     */
    protected ?string $connection = null;

    /**
     * The model's attributes
     */
    protected array $attributes = [];

    /**
     * The model's original attributes
     */
    protected array $original = [];

    /**
     * Indicates if the model exists in database
     */
    protected bool $exists = false;

    /**
     * Fillable attributes for mass assignment
     */
    protected array $fillable = [];

    /**
     * Guarded attributes (not mass assignable)
     */
    protected array $guarded = ['*'];

    /**
     * Indicates if timestamps are enabled
     */
    protected bool $timestamps = true;

    /**
     * The name of the created_at column
     */
    protected string $createdAt = 'created_at';

    /**
     * The name of the updated_at column
     */
    protected string $updatedAt = 'updated_at';

    /**
     * Create a new model instance
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        if (!isset($this->table)) {
            $this->table = $this->getTableName();
        }

        $this->fill($attributes);
    }

    /**
     * Get the table name for the model
     *
     * @return string
     */
    protected function getTableName(): string
    {
        // Convert class name to snake_case plural
        $className = (new \ReflectionClass($this))->getShortName();
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . 's';
    }

    /**
     * Create a new query builder for the model
     *
     * @return QueryBuilder
     */
    public static function query(): QueryBuilder
    {
        $instance = new static();
        return Database::table($instance->table, $instance->connection);
    }

    /**
     * Find a model by its primary key
     *
     * @param mixed $id
     * @return static|null
     */
    public static function find($id): ?static
    {
        $instance = new static();
        $result = static::query()->find($id, $instance->primaryKey);

        return $result ? static::hydrate($result) : null;
    }

    /**
     * Find a model by its primary key or throw an exception
     *
     * @param mixed $id
     * @return static
     * @throws \Exception
     */
    public static function findOrFail($id): static
    {
        $model = static::find($id);

        if ($model === null) {
            throw new \Exception("Model not found with id: {$id}");
        }

        return $model;
    }

    /**
     * Get all models
     *
     * @return array
     */
    public static function all(): array
    {
        $results = static::query()->get();
        return array_map([static::class, 'hydrate'], $results);
    }

    /**
     * Get the first model
     *
     * @return static|null
     */
    public static function first(): ?static
    {
        $result = static::query()->first();
        return $result ? static::hydrate($result) : null;
    }

    /**
     * Create a WHERE query
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return QueryBuilder
     */
    public static function where(string $column, string $operator, $value = null): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    /**
     * Create a new model and save it to database
     *
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();

        return $model;
    }

    /**
     * Save the model to the database
     *
     * @return bool
     */
    public function save(): bool
    {
        if ($this->exists) {
            return $this->performUpdate();
        }

        return $this->performInsert();
    }

    /**
     * Perform an insert operation
     *
     * @return bool
     */
    protected function performInsert(): bool
    {
        if ($this->timestamps) {
            $this->updateTimestamps();
        }

        $id = static::query()->insert($this->attributes);

        $this->setAttribute($this->primaryKey, $id);
        $this->exists = true;
        $this->syncOriginal();

        return true;
    }

    /**
     * Perform an update operation
     *
     * @return bool
     */
    protected function performUpdate(): bool
    {
        if ($this->timestamps) {
            $this->attributes[$this->updatedAt] = date('Y-m-d H:i:s');
        }

        $dirty = $this->getDirty();

        if (empty($dirty)) {
            return true;
        }

        static::query()
            ->where($this->primaryKey, '=', $this->getAttribute($this->primaryKey))
            ->update($dirty);

        $this->syncOriginal();

        return true;
    }

    /**
     * Delete the model from database
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        static::query()
            ->where($this->primaryKey, '=', $this->getAttribute($this->primaryKey))
            ->delete();

        $this->exists = false;

        return true;
    }

    /**
     * Update timestamps
     *
     * @return void
     */
    protected function updateTimestamps(): void
    {
        $time = date('Y-m-d H:i:s');

        if (!$this->exists && !isset($this->attributes[$this->createdAt])) {
            $this->setAttribute($this->createdAt, $time);
        }

        if (!isset($this->attributes[$this->updatedAt])) {
            $this->setAttribute($this->updatedAt, $time);
        }
    }

    /**
     * Fill the model with attributes
     *
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Check if an attribute is fillable
     *
     * @param string $key
     * @return bool
     */
    protected function isFillable(string $key): bool
    {
        // If fillable is defined, only allow those
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable);
        }

        // If guarded is *, nothing is fillable
        if (in_array('*', $this->guarded)) {
            return false;
        }

        // Otherwise, allow if not in guarded
        return !in_array($key, $this->guarded);
    }

    /**
     * Set an attribute
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get an attribute
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Get all attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get the dirty attributes
     *
     * @return array
     */
    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Sync the original attributes with the current
     *
     * @return void
     */
    protected function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    /**
     * Hydrate a model from database result
     *
     * @param mixed $result
     * @return static
     */
    protected static function hydrate($result): static
    {
        $model = new static();
        $attributes = is_object($result) ? get_object_vars($result) : $result;

        $model->attributes = $attributes;
        $model->original = $attributes;
        $model->exists = true;

        return $model;
    }

    /**
     * Define a one-to-one relationship
     *
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return mixed
     */
    protected function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null)
    {
        $instance = new $related();
        $foreignKey = $foreignKey ?? $this->table . '_' . $this->primaryKey;
        $localKey = $localKey ?? $this->primaryKey;

        return $instance::query()
            ->where($foreignKey, '=', $this->getAttribute($localKey))
            ->first();
    }

    /**
     * Define a one-to-many relationship
     *
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return array
     */
    protected function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): array
    {
        $instance = new $related();
        $foreignKey = $foreignKey ?? $this->table . '_' . $this->primaryKey;
        $localKey = $localKey ?? $this->primaryKey;

        $results = $instance::query()
            ->where($foreignKey, '=', $this->getAttribute($localKey))
            ->get();

        return array_map([$related, 'hydrate'], $results);
    }

    /**
     * Define an inverse relationship
     *
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $ownerKey
     * @return mixed
     */
    protected function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null)
    {
        $instance = new $related();
        $foreignKey = $foreignKey ?? $instance->table . '_' . $instance->primaryKey;
        $ownerKey = $ownerKey ?? $instance->primaryKey;

        return $instance::query()
            ->where($ownerKey, '=', $this->getAttribute($foreignKey))
            ->first();
    }

    /**
     * Convert model to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Convert model to JSON
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Magic getter
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Convert to string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}
