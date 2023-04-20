<?php declare(strict_types=1);
namespace Restless\Core;

/**
* Represents the base class that custom exception classes derive from
*
* @author  : Victor D. Sandiego
*/
abstract class BaseException extends \Exception
{
  private $request;

  protected function __construct($message, int $code, ?CoreRequest $request = null)
  {
    parent::__construct($message, $code);
    $this->request = $request;
  }

  /**
  * Gets the CoreRequest object associated with the exception, or null if none
  *
  * @return CoreRequest|null
  */
  public function getRequest() : ?CoreRequest
  {
    return $this->request;
  }
}
?>