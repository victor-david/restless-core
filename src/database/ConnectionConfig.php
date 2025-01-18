<?php declare(strict_types=1);
namespace Restless\Database;

/**
 * Provides configuration values to access a database
 */
final class ConnectionConfig
{
    /**
     * Name of the database
     *
     * @var string
     */
    public $database;

    /**
     * Host for the database
     *
     * @var string
     */
    public $host;

    /**
     * Password to connect
     *
     * @var string
     */
    public $password;

    /**
     * Persistent connection, default is true
     *
     * @var bool
     */
    public $persist;

    /**
     * Prefix, default mysql
     *
     * @var string
     */
    public $prefix;

    /**
     * Connection timeout, default 5 seconds
     *
     * @var int
     */
    public $timeout;

    /**
     * Name of database user
     *
     * @var string
     */
    public $user;

    /**
     * Version
     *
     * @var string
     */
    public $version;

    /**
     * Guid. Some implementors assign a guid to the connection info
     *
     * @var string|null
     */
    public $guid;

    public function __construct()
    {
        $this->prefix = 'mysql';
        $this->persist = true;
        $this->timeout = 5;
    }

    public function __clone()
    {
        unset($this->password);
        unset($this->user);
    }
}
?>