<?php declare(strict_types=1);
namespace Restless\Core;

/**
 * Represents a security exception.
 *
 * This class extends \Exception and requires a log message that is separate
 * from the standard exception message.
 */
class SecurityException extends \Exception
{
    /**
     * @var int
     */
    public $logLevel;

    /**
     * @var string
     */
    public $logMessage;

    /**
     * @var array
     */
    public $logData;

    private function __construct(string $message, string $logMessage, array $logData = [], int $logLevel = 100, $previous = null)
    {
        parent::__construct($message, $logLevel, $previous);
        $this->logMessage = $logMessage;
        $this->logData = $logData;
        $this->logLevel = $logLevel;
    }

    /**
     * Throws a SecurityException.
     *
     * @param string $message The exception message
     * @param string $logMessage Log message
     * @param mixed $logData Extra data that can be used for a log.
     * @param int $logLevel
     * @param mixed $previous
     */
    public static function throwException(string $message, string $logMessage, array $logData = [], int $logLevel = 100, $previous = null)
    {
        throw new self($message, $logMessage, $logData, $logLevel, $previous);
    }
}
?>