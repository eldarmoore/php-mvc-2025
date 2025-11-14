<?php

namespace Core\Security;

/**
 * Hash Class
 *
 * Provides secure password hashing and verification using bcrypt.
 */
class Hash
{
    /**
     * Default algorithm for hashing
     */
    protected static string $algorithm = PASSWORD_BCRYPT;

    /**
     * Default cost for bcrypt
     */
    protected static int $cost = 10;

    /**
     * Hash a password
     *
     * @param string $password
     * @param array $options
     * @return string
     */
    public static function make(string $password, array $options = []): string
    {
        $cost = $options['cost'] ?? static::$cost;

        return password_hash($password, static::$algorithm, ['cost' => $cost]);
    }

    /**
     * Verify a password against a hash
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function check(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if a hash needs to be rehashed
     *
     * @param string $hash
     * @param array $options
     * @return bool
     */
    public static function needsRehash(string $hash, array $options = []): bool
    {
        $cost = $options['cost'] ?? static::$cost;

        return password_needs_rehash($hash, static::$algorithm, ['cost' => $cost]);
    }

    /**
     * Generate a random string
     *
     * @param int $length
     * @return string
     */
    public static function random(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
