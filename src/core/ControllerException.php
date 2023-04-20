<?php declare(strict_types=1);
namespace Restless\Core;

/**
* Represents an exception that occurs during controller initialization
*
* @author  : Victor D. Sandiego
*/
class ControllerException extends BaseException
{
  const HTTP_NOT_FOUND = 404;
  const HTTP_INTERNAL_ERROR = 500;
  const HTTP_SERVICE_UNAVAILABLE = 503;

  private function __construct($message, int $code, ?CoreRequest $request)
  {
    parent::__construct($message, $code, $request);
  }

  /**
  * Throws a ControllerException with exception code set to self::HTTP_NOT_FOUND
  *
  * @param string $message
  * @param CoreRequest|null $request
  */
  public static function throwControllerNotFoundException(string $message, ?CoreRequest $request = null)
  {
    throw new self($message, self::HTTP_NOT_FOUND, $request);
  }

  /**
  * Throws a ControllerException with exception code set to self::HTTP_NOT_FOUND
  *
  * @param string $message
  * @param CoreRequest|null $request
  */
  public static function throwMethodNotFoundException(string $message, ?CoreRequest $request = null)
  {
    throw new self($message, self::HTTP_NOT_FOUND, $request);
  }

  /**
  * Throws a ControllerException with exception code set to self::HTTP_INTERNAL_ERROR
  *
  * @param string $message
  * @param CoreRequest|null $request
  */
  public static function throwMethodDirectException(string $message, ?CoreRequest $request = null)
  {
    throw new self($message, self::HTTP_INTERNAL_ERROR, $request);
  }
}
?>