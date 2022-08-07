<?php declare(strict_types=1);
namespace Restless\Core;
/**
* Represents an exception that occurs during view presentation
*
* @author  : Victor D. Sandiego
*/
class ViewException extends \Exception
{
  /**
   * Class constructor
   *
   * @param string $message Exception message
   * @param int $code
   * @param mixed $previous
  */
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