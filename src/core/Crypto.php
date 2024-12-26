<?php declare(strict_types=1);
namespace Restless\Core;
/**
 * Provides static methods for hashing and verifying passwords.
 *
 * The hashing and verification methods of this class are simple wrappers for the password functions that were included in PHP 5.5.0
 */
final class Crypto
{
    /**
     * Hashes the specified password using the password_hash function.
     *
     * @param string $password The plain text password
     *
     * @return string The hashed password
     */
    public static function passwordHash($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verifies the password matches the hash. A simple wrapper for password_verify
     *
     * @param string $password The plain text password
     * @param string $hash The password hash, generally obtained from the database during a login attempt
     *
     * @return boolean true if okay, otherwise false.
     */
    public static function passwordVerify($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Creates a key of the specified length
     *
     * @param int $length
     *
     * @return string
     */
    public static function generateKey(int $length) : string
    {
        $result = '';
        for ($k = 0; $k < $length; $k++)
        {
            $result .= chr(random_int(33, 126));
        }
        return $result;
    }

    private const IDCHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * Generates and returns an case sensitive alpha numeric id with no repeated characters.
     *
     * @param mixed $prefix
     * @param int $length Max is 62
     *
     * @return string
     */
    public static function generateId($prefix, int $length) : string
    {
        $result = '';
        $max = strlen(self::IDCHARS);

        $length = ($length > $max) ? $max : $length;

        $length -= strlen($prefix);

        if ($length < 2)
        {
            throw new \Exception('Invalid length for id');
        }

        $used = [];

        for ($k = 0; $k < $length; $k++)
        {
            $found = false;
            while (!$found)
            {
                $i = random_int(0, $max - 1);
                if (!in_array($i, $used))
                {
                    $used[] = $i;
                    $result .= self::IDCHARS[$i];
                    $found = true;
                }
            }
        }

        return $prefix . $result;
    }
}
?>