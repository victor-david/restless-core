<?php  declare(strict_types=1);
namespace Restless\Log;

/**
* Represents the base class for handlers. This class must be inherited.
*/
abstract class AbstractHandler implements HandlerInterface
{
  /**
  * @var int
  */
  protected $level = LogLevel::DEBUG;

  /**
  * @var callable|null
  */
  protected $isHandledCallback;

  /**
  * @var callable[]
  */
  protected $processors;

  /**
  * @param int Default minimum logging level.
  */
  public function __construct(int $level = LogLevel::DEBUG)
  {
    $this->level = $level;
    $this->processors = [];
  }

  /**
  * Adds a processor in the stack.
  *
  * @param  ProcessorInterface|callable $callback
  * @return HandlerInterface
  */
  public function pushProcessor(callable $callback): HandlerInterface
  {
    array_unshift($this->processors, $callback);
    return $this;
  }

  /**
  * Removes the processor on top of the stack and returns it.
  *
  * @throws \LogicException  In case the processor stack is empty
  * @return callable|ProcessorInterface
  */
  public function popProcessor(): callable
  {
    if (!$this->processors)
    {
      throw new \LogicException('Empty processor stack.');
    }
    return array_shift($this->processors);
  }

  /**
  * Sets a callback that is used to provide custom is-handled logic.
  *
  * The callback receives a single parameter of type LogMessageObject.
  *
  * @param callable
  * @return HandlerInterface
  */
  public function setIsHandled(callable $callback): HandlerInterface
  {
    $this->isHandledCallback = $callback;
    return $this;
  }

  /**
  * Gets a boolean that indicates if the handler can handle the message.
  *
  * This method returns true if $msg->level is greater than or equal to $this->level.
  * Override if you need other logic, don't use this at all, or just use other statements.
  * This is basically a convenience method.
  *
  * @param LogMessageObject
  */
  public function getIsHandler(LogMessageObject $msg): bool
  {
    return $msg->level >= $this->level;
  }

  /**
  * Gets a boolean that indicates if the handler handled the message.
  *
  * By default, this method returns true if $msg->level is less than or equal to $this->level.
  * To provide custom logic, inject a callback using the setIsHandled($callback) method.
  *
  * Derived classes should call this method at the end of their handle($msg) method
  * instead of setting $msg->handled directly; this allows classes that further extend
  * a derived class (or the consumer) to provide custom is-handled logic if needed.
  *
  * @param LogMessageObject
  */
  public function getIsHandled(LogMessageObject $msg): bool
  {
    if ($this->isHandledCallback)
    {
      return ($this->isHandledCallback)($msg);
    }
    return $msg->level <= $this->level;
  }

  /**
  * Calls all processors assigned to this instance.
  * Derived classes should call this method from their handle(LogMessageObject $msg)
  * method, i.e: $this->processMessage($msg); before continuing to handle the message.
  *
  * @param LogMessageObject $msg
  */
  protected function processMessage(LogMessageObject $msg)
  {
    foreach ($this->processors as $processor)
    {
      $processor($msg);
    }
  }


}
?>