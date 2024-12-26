<?php declare(strict_types=1);
namespace Restless\Log;

interface ProcessorInterface
{
    public function __invoke(LogMessageObject $msg);
}
?>