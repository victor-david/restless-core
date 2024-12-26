<?php declare(strict_types=1);
namespace Restless\Log;

use Psr\Log\LogLevel as PSRL;
use Psr\Log\InvalidArgumentException;

class LogLevel
{
    /**
     * Detailed debug information
     */
    public const DEBUG = 100;

    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    public const INFO = 200;

    /**
     * Uncommon events
     */
    public const NOTICE = 300;

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    public const WARNING = 400;

    /**
     * Runtime errors
     */
    public const ERROR = 500;

    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    public const CRITICAL = 600;

    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    public const ALERT = 700;

    /**
     * Urgent alert.
     */
    public const EMERGENCY = 800;

    /**
     * This is a static variable and not a constant to serve as an extension point for custom levels
     *
     * @var array<int, string> $levels Logging levels with the levels as key
     *
     * @phpstan-var array<Level, LevelName> $levels Logging levels with the levels as key
     */
    protected static $levels =
    [
        self::DEBUG     => PSRL::DEBUG,
        self::INFO      => PSRL::INFO,
        self::NOTICE    => PSRL::NOTICE,
        self::WARNING   => PSRL::WARNING,
        self::ERROR     => PSRL::ERROR,
        self::CRITICAL  => PSRL::CRITICAL,
        self::ALERT     => PSRL::ALERT,
        self::EMERGENCY => PSRL::EMERGENCY,
    ];

    /**
     * Gets the name of the logging level.
     *
     * @throws \Psr\Log\InvalidArgumentException If level is not defined
     *
     * @phpstan-param  Level     $level
     * @phpstan-return LevelName
     */
    public static function getLevelName(int $level): string
    {
        if (!isset(static::$levels[$level]))
        {
            throw new InvalidArgumentException('Invalid level (name)');
        }

        return static::$levels[$level];
    }

    /**
     * Gets the integer log level from the specified level.
     *
     * @param mixed $level
     * @return int
     * @throws \Psr\Log\InvalidArgumentException if level is invalid.
     */
    public static function toLogLevel($level): int
    {
        if (is_string($level))
        {
            if (is_numeric($level))
            {
                static::validateLogLevel(intval($level));
                return intval($level);
            }

            $upper = strtr($level, 'abcdefgilmnortuwy', 'ABCDEFGILMNORTUWY');
            if (defined(__CLASS__.'::' . $upper))
            {
                return constant(__CLASS__ . '::' . $upper);
            }

            throw new InvalidArgumentException('Invalid level');
        }

        static::validateLogLevel($level);

        return $level;
    }

    private static function validateLogLevel($level)
    {
        if (
            is_int($level) &&
            $level != LogLevel::EMERGENCY &&
            $level != LogLevel::ALERT &&
            $level != LogLevel::CRITICAL &&
            $level != LogLevel::ERROR &&
            $level != LogLevel::WARNING &&
            $level != LogLevel::NOTICE &&
            $level != LogLevel::INFO &&
            $level != LogLevel::DEBUG
        )
        {
            throw new InvalidArgumentException('Invalid log level');
        }
    }
}
?>