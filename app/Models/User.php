<?php

namespace App\Models;

use Core\Database\Model;
use Core\Security\Hash;

/**
 * User Model
 *
 * Example model demonstrating the framework's ORM capabilities.
 */
class User extends Model
{
    /**
     * The table associated with the model
     */
    protected string $table = 'users';

    /**
     * The primary key for the model
     */
    protected string $primaryKey = 'id';

    /**
     * Fillable attributes for mass assignment
     */
    protected array $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Indicates if timestamps are enabled
     */
    protected bool $timestamps = true;

    /**
     * Create a new user
     *
     * @param array $attributes
     * @return static
     */
    public static function createUser(array $attributes): static
    {
        // Hash the password before saving
        if (isset($attributes['password'])) {
            $attributes['password'] = Hash::make($attributes['password']);
        }

        return static::create($attributes);
    }

    /**
     * Verify a user's password
     *
     * @param string $password
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        return Hash::check($password, $this->password);
    }

    /**
     * Get user's posts (example relationship)
     *
     * @return array
     */
    public function posts(): array
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    /**
     * Hide password from array/JSON output
     *
     * @return array
     */
    public function toArray(): array
    {
        $attributes = parent::toArray();
        unset($attributes['password']);
        return $attributes;
    }
}
