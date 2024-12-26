<?php declare(strict_types=1);
namespace Restless\Log;

/**
* Interface for log handlers
*/
interface HandlerInterface
{
    /**
     * Adds a processor in the stack.
     *
     * @param  ProcessorInterface|callable $callback
     *
     * @return HandlerInterface
     */
    public function pushProcessor(callable $callback): HandlerInterface;

    /**
     * Removes the processor on top of the stack and returns it.
     *
     * @throws \LogicException  In case the processor stack is empty
     *
     * @return callable|ProcessorInterface
     */
    public function popProcessor(): callable;

    /**
     * Handles a log message.
     *
     * All records may be passed to this method; the handler should ignore
     * those that it does not want to handle.
     *
     * If the handler sets $msg->handled = true, the handler stack stops.
     *
     * @param \Restless\Log\LogMessageObject $msg The message to handle
     */
    public function handle(\Restless\Log\LogMessageObject $msg);

    /**
     * Sets a callback that is used to provide custom is-handled logic.
     *
     * @param callable
     *
     * @return HandlerInterface
     */
    public function setIsHandled(callable $callback): HandlerInterface;
}
?>