<?php declare(strict_types=1);
namespace Restless\Core;
/**
 * Represents an exception that occurs during view presentation
 *
 */
class ViewException extends \Exception
{
    public function __construct($message, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function throwViewException($message, $code = 0, $previous = null)
    {
        throw new self($message, $code, $previous);
    }
}
?>