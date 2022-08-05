<?php declare(strict_types=1);
namespace Restless\Log;

use Stringable;
use Throwable;

class Logger implements \Psr\Log\LoggerInterface
{
  /**
  * @var string
  */
  protected $name;

  /**
  * @var HandlerInterface[]
  */
  protected $handlers;

  /**
  * Processors that will process all log records
  *
  * To process records of a single handler, add processor(s) on the handler itself.
  *
  * @var callable[]
  */
  protected $processors;

  /**
  * @var DateTimeZone
  */
  protected $timezone;

  /**
  * @var callable|null
  */
  protected $exceptionHandler;

  /**
  * Class constuctor
  *
  * @param string $name
  */
  public function __construct(string $name)
  {
    $this->name = $name;
    $this->handlers = [];
    $this->processors = [];
    $this->timezone = new \DateTimeZone('UTC');
  }

  /**
  * Sets the time zone
  *
  * @param string $timezone
  */
  public function setTimeZone(string $timezone)
  {
    $this->timezone = new \DateTimeZone($timezone);
  }

  /**
  * Set a custom exception handler that will be called if adding a new record fails
  *
  * The callable will receive an exception object and the log message that failed to be logged
  */
  public function setExceptionHandler(?callable $callback): self
  {
    $this->exceptionHandler = $callback;
    return $this;
  }

  /**
  * Pushes a handler on to the stack.
  */
  public function pushHandler(HandlerInterface $handler): self
  {
    array_unshift($this->handlers, $handler);
    return $this;
  }

  /**
  * Pops a handler from the stack
  *
  * @throws \LogicException If empty handler stack
  */
  public function popHandler(): HandlerInterface
  {
    if (!$this->handlers)
    {
      throw new \LogicException('Empty handler stack.');
    }
    return array_shift($this->handlers);
  }

  /**
  * Adds a processor on to the stack.
  */
  public function pushProcessor(callable $callback): self
  {
    array_unshift($this->processors, $callback);
    return $this;
  }

  /**
  * Removes the processor on top of the stack and returns it.
  *
  * @throws \LogicException If empty processor stack
  * @return callable
  */
  public function popProcessor(): callable
  {
    if (!$this->processors)
    {
      throw new \LogicException('Empty processor stack.');
    }
    return array_shift($this->processors);
  }

  /* LoggerInterface */

  /**
  * System is unusable.
  *
  * @param string|Stringable $message
  * @param mixed[] $context
  *
  * @return void
  */
  public function emergency(string|Stringable $message, array $context = []): void
  {
    $this->addRecord(LogLevel::EMERGENCY, $message, $context);
  }

  /**
  * Action must be taken immediately.
  *
  * Example: Entire website down, database unavailable, etc. This should
  * trigger the SMS alerts and wake you up.
  *
  * @param string|Stringable $message
  * @param mixed[] $context
  *
  * @return void
  */
  public function alert(string|Stringable $message, array $context = []): void
  {
    $this->addRecord(LogLevel::ALERT, $message, $context);
  }

  /**
  * Critical conditions.
  *
  * Example: Application component unavailable, unexpected exception.
  *
  * @param string|Stringable $message
  * @param mixed[] $context
  *
  * @return void
  */
  public function critical(string|Stringable $message, array $context = []): void
  {
    $this->addRecord(LogLevel::CRITICAL, $message, $context);
  }

  /**
  * Runtime errors that do not require immediate action but should typically
  * be logged and monitored.
  *
  * @param string|Stringable $message
  * @param mixed[] $context
  *
  * @return void
  */
  public function error(string|Stringable $message, array $context = []): void
  {
    $this->addRecord(LogLevel::ERROR, $message, $context);
  }

  /**
  * Exceptional occurrences that are not errors.
  *
  * Example: Use of deprecated APIs, poor use of an API, undesirable things
  * that are not necessarily wrong.
  *
  * @param string|Stringable $message
  * @param mixed[] $context
  *
  * @return void
  */
  public function warning(string|Stringable $message, array $context = []): void
  {
    $this->addRecord(LogLevel::WARNING, $message, $context);
  }

  /**
  * Normal but significant events.
  *
  * @param string|Stringable $message
  * @param mixed[] $context
  *
  * @return void
  */
  public function notice(string|Stringable $message, array $context = []): void
  {
    $this->addRecord(LogLevel::NOTICE, $message, $context);
  }

  /**
  * Interesting events.
  *
  * Example: User logs in, SQL logs.
  *
  * @param string|Stringable $message
  * @param mixed[] $context
  *
  * @return void
  */
  public function info(string|Stringable $message, array $context = []): void
  {
    $this->addRecord(LogLevel::INFO, $message, $context);
  }

  /**
  * Detailed debug information.
  *
  * @param string|\Stringable $message
  * @param mixed[] $context
  *
  * @return void
  */
  public function debug(string|Stringable $message, array $context = []): void
  {
    $this->addRecord(LogLevel::DEBUG, $message, $context);
  }

  /**
  * Logs with an arbitrary level.
  *
  * @param mixed   $level
  * @param string|Stringable $message
  * @param mixed[] $context
  *
  * @return void
  *
  * @throws \Psr\Log\InvalidArgumentException
  */
  public function log($level, string|Stringable $message, array $context = []): void
  {
    $level = LogLevel::toLogLevel($level);
    $this->addRecord($level, $message, $context);
  }

  /**
  * Delegates exception management to the custom exception handler,
  * or throws the exception if no custom handler is set.
  *
  * @param Thowable
  * @param LogMessageObject
  */
  protected function handleException(Throwable $e, LogMessageObject $msg): void
  {
    if (!$this->exceptionHandler)
    {
      throw $e;
    }

    ($this->exceptionHandler)($e, $msg);
  }

  /**
  * Adds a log record.
  *
  * @param  int     $level   The logging level
  * @param  string  $message The log message
  * @param  mixed[] $context The log context
  * @return bool    Whether the record has been processed
  */
  private function addRecord(int $level, string|Stringable $message, array $context = []): bool
  {
    $msg = new LogMessageObject(
    [
      'channel' => $this->name,
      'context' => $context,
      'datetime' =>  new \DateTimeImmutable('now', $this->timezone),
      'level' => $level,
      'levelName' => LogLevel::getLevelName($level),
      'message' => ($message instanceof Stringable) ? $message->__toString() : $message
    ]);

    foreach ($this->processors as $processor)
    {
      try
      {
        $processor($msg);
      }
      catch (Throwable $e)
      {
        $this->handleException($e, $msg);
      }
    }

    foreach ($this->handlers as $handler)
    {
      try
      {
        $handler->handle($msg);
        if ($msg->handled)
        {
          return true;
        }
      }
      catch (Throwable $e)
      {
        $this->handleException($e, $msg);
      }
    }
    return false;
  }
}
?>