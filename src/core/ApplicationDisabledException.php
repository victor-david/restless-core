<?php declare(strict_types=1);
namespace Restless\Core;

/**
* Represents an exception that occurs when an application is disabled
*
* @author  : Victor D. Sandiego
*/
class ApplicationDisabledException extends BaseException
{
  /**
  * Class constructor
  */
  private function __construct(?CoreRequest $request)
  {
    parent::__construct('Application disabled', ControllerException::HTTP_SERVICE_UNAVAILABLE, $request);
  }

  /**
  * Throws an ApplicationDisabledException
  */
  public static function throwApplicationDisabled(?CoreRequest $request = null)
  {
    throw new self($request);
  }
}
?>