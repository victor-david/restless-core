<?php declare(strict_types=1);
namespace Restless\Log;

use DateTimeInterface;

/**
* Represents data that is sent in a log message.
*/
class LogMessageObject
{
  /**
  * @var string
  */
  public $channel;

  /**
  * @var array|object
  */
  public $context;

  /**
  * @var \DateTimeInterface
  */
  public $datetime;

  /**
  * @var array|object
  */
  public $extra;

  /**
  * @var bool
  */
  public $handled;

  /**
  * @var int
  */
  public $level;

  /**
  * @var string
  */
  public $levelName;

  /**
  * @var string
  */
  public $message;

  /**
  * Class constructor
  *
  * @param array $values
  */
  public function __construct(array $values = [])
  {
    foreach($values as $key=>$value)
    {
      $this->$key = $value;
    }

    $this->extra = [];
    $this->handled = false;
  }

  /**
  * Gets a tab delimited single line string representation of this message object.
  *
  * @param string|null $dateFormat
  * @return string
  */
  public function toString(?string $dateFormat = DateTimeInterface::ATOM) : string
  {
    $date = $this->datetime->format($dateFormat);
    $address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'n/a';
    $context = is_array($this->context) ? json_encode($this->context) : '[]';
    $extra = is_array($this->extra) ? json_encode($this->extra) : '[]';
    return
      "$date\t{$this->channel}\t{$this->level}\t{$this->levelName}\t" .
      "{$this->message}\t{$address}\t{$context}\t{$extra}";
  }

  /**
  * Calls toString() with default date format DateTimeInterface::ATOM
  */
  public function __toString()
  {
    return $this->toString(DateTimeInterface::ATOM);
  }
}
?>