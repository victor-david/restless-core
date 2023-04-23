<?php declare(strict_types=1);
namespace Restless\Core;

/**
* Represents an exception that occurs during controller initialization
*
* @author  : Victor D. Sandiego
*/
class ControllerException extends \Exception
{
  const HTTP_NOT_FOUND = 404;
  const HTTP_INTERNAL_ERROR = 500;
  const HTTP_SERVICE_UNAVAILABLE = 503;

  /**
   * Class constructor
   *
   * @param string $message Exception message
   * @param CoreRequest
   * @param int $code
   * @param mixed $previous
  */
  private function __construct($message, int $code, $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }

  /**
  * Throws a ControllerException with exception code set to self::HTTP_NOT_FOUND
  *
  * @param string $message
  */
  public static function throwControllerNotFoundException(string $message)
  {
    throw new self($message, self::HTTP_NOT_FOUND);
  }

  /**
  * Throws a ControllerException with exception code set to self::HTTP_NOT_FOUND
  *
  * @param string $message
  */
  public static function throwMethodNotFoundException(string $message)
  {
    throw new self($message, self::HTTP_NOT_FOUND);
  }

  /**
  * Throws a ControllerException with exception code set to self::HTTP_INTERNAL_ERROR
  *
  * @param string $message
  */
  public static function throwMethodDirectException(string $message)
  {
    throw new self($message, self::HTTP_INTERNAL_ERROR);
  }
}
?>