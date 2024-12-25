<?php declare(strict_types=1);
namespace Restless\Core;

/**
 * Represents a single application.
 *
 * This class defines properties that all applications must implement.
 *
 * @abstract
 */
abstract class App extends StrictObject
{
    /**
     * Unique integer id of the application.
     *
     * @var int
     */
    public $id;

    /**
     * Unique string id of the application.
     *
     * @var string
     */
    public $xid;

    /**
     * Unique application key, maps to app directories.
     */
    public $key;
}
?>