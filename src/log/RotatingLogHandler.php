<?php declare(strict_types=1);
namespace Restless\Log;

use DateTime;
use DateTimeZone;
use DateTimeInterface;

/**
* Provides a handler that writes rotating logs to a specified file system directory.
*
* Writes rotating logs named according to the current date (UTC). Files that are generated
* take the form of 2022-07-22.log, 2022-07-23.log, etc.
*/
class RotatingLogHandler extends AbstractHandler
{
  /**
  * @var string
  */
  protected $path;

  /**
  * Class constructor
  *
  * @param string Path to the directory where log files are to be written.
  * @param int Default minimum logging level.
  */
  public function __construct(string $path, int $level = LogLevel::DEBUG)
  {
    parent::__construct($level);
    $this->path = $path;
    $this->prepare();
    $this->pushProcessor(new PsrLogMessageProcessor());
  }

  /**
  * Handles a log message.
  */
  public function handle(LogMessageObject $msg) : void
  {
    if ($this->getIsHandler($msg))
    {
      $this->processMessage($msg);

      $date = new DateTime('now', new DateTimeZone('UTC'));
      $file = $this->path . DIRECTORY_SEPARATOR . $date->format('Y-m-d') . '.log';

      file_put_contents($file, $msg->toString() . PHP_EOL, FILE_APPEND | LOCK_EX);

      $msg->handled = $this->getIsHandled($msg);
    }
  }

  private function prepare()
  {
    if (!strlen($this->path) || is_file($this->path))
    {
      throw new \InvalidArgumentException('Invalid path');
    }

    if (!is_dir($this->path))
    {
      if (!@mkdir($this->path, 0755, true))
      {
        throw new \InvalidArgumentException('Unable to create log directory');
      }
    }
  }
}
?>