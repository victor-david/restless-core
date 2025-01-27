<?php declare(strict_types=1);
namespace Restless\Core;

/**
 * Represents an authentication exception, one that is thrown during login.
 */
class AuthenticationException extends \Exception
{
    /**
     * The user id that threw the exception. Zero if unknown.
     *
     * @var int
     */
    public $userId;

    private function __construct(string|null $message, int $userId, $previous)
    {
        $this->userId = $userId;
        parent::__construct($message ?? 'Invalid user id or password', 0, $previous);
    }

    /**
     * Throws an AuthenicationException
     */
    public static function throwAuthenticationException(string|null $message = null, int $userId = 0, $previous = null)
    {
        throw new self($message, $userId, $previous);
    }
}
?>