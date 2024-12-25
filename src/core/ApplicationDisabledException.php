<?php declare(strict_types=1);
namespace Restless\Core;

/**
 * Represents an exception that occurs when an application is disabled
 *
 */
class ApplicationDisabledException extends \Exception
{
    private function __construct()
    {
        parent::__construct('Application disabled', ControllerException::HTTP_SERVICE_UNAVAILABLE, null);
    }

    /**
     * Throws an ApplicationDisabledException
     */
    public static function throwApplicationDisabled()
    {
        throw new self();
    }
}
?>