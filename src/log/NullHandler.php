<?php declare(strict_types=1);
namespace Restless\Log;

/**
 * Provides a handler that marks the log message as handled without doing anything.
 */
class NullHandler extends AbstractHandler
{
    /**
     * Handles a log message.
     */
    public function handle(LogMessageObject $msg) : void
    {
        $msg->handled = true;
    }
}
?>