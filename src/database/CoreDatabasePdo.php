<?php declare(strict_types=1);
namespace Restless\Database;

use PDO;
use PDOException;
use Exception;

/**
 * Provides underlying methods and properties for a high-level database object.
 */
class CoreDatabasePdo
{
    /**
     * @var ConnectionConfig
     */
    protected $connectionConfig;

    /**
     * @var \PDO
     */
    protected $connection;

    /**
     * @var int
     */
    protected $rowCount;

    /**
     * @var string
     */
    public $timeZone;

    /**
     * @var string
     */
    public $locale;

    /**
     * The format string used to make a date string.
     *
     * @var string
     */
    public $dateFormatShort = '%b %d, %Y';

    /**
     * The format string used to make a long date and time string.
     *
     * @var string
     */
    public $dateFormatFull = '%b %d, %Y %T';

    public function __construct()
    {
        $this->connection = null;
        $this->rowCount = 0;
    }

    /**
     * Sets the short date format
     */
    public function setDateFormatShort(string|null $value)
    {
        if ($value)
        {
            $this->dateFormatShort = $value;
        }
    }

    /**
     * Setes the full date format
     */
    public function setDateFormatFull(string|null $value)
    {
        if ($value)
        {
            $this->dateFormatFull = $value;
        }
    }

    /**
     * Sets the database configuration
     *
     * @param ConnectionConfig $config
     */
    public function setConnectionConfig(ConnectionConfig $config)
    {
        $this->connectionConfig = $config;
    }

    /**
     * Gets a clone of the data connection info (user and password removed)
     *
     * @return ConnectionConfig
     */
    public function getConnectionInfo() : ConnectionConfig
    {
        $obj = clone $this->connectionConfig;
        $obj->version = $this->adhoc('select version()')->execute()->fetchColumn(0);
        return $obj;
    }

    /**
     * Sets the time zone to the specified value
     *
     * $param string $value The time zone string
     */
    public function setTimeZone(string $value)
    {
        if ($this->connectionConfig->prefix == 'mysql')
        {
            $this->adhoc('SET time_zone=?')->parms($value)->execute();
            $this->timeZone = $value;
        }
    }

    /**
     * Sets the locale to the specified value
     *
     * $param string $value The locale string
     */
    public function setLocale(string $value)
    {
        if ($this->connectionConfig->prefix == 'mysql')
        {
            $this->adhoc('SET lc_time_names=?')->parms($value)->execute();
            $this->locale = $value;
        }
    }

    /**
     * Begins a transaction if one is not already in progress
     */
    public final function beginTransaction()
    {
        $this->connectDemand();
        $this->validateConnection();
        if (!$this->connection->inTransaction())
        {
            $this->connection->beginTransaction();
        }
    }

    /**
     * Commits a transaction if one is in progress
     */
    public final function commitTransaction()
    {
        $this->connectDemand();
        $this->validateConnection();
        if ($this->connection->inTransaction())
        {
            $this->connection->commit();
        }
    }

    /**
     * Rolls back a transaction if one is in progress
     */
    public final function rollbackTransaction()
    {
        $this->connectDemand();
        $this->validateConnection();
        if ($this->connection->inTransaction())
        {
            $this->connection->rollBack();
        }
    }

    /**
     * Begins a SELECT operation
     *
     * @param string $table
     * @param null $alias
     *
     * @return PDOQueryObject
     */
    public function selectFrom(string $table, string|null $alias = null) : PDOQueryObject
    {
        return $this->createQueryObject($table, $alias, PDOQueryObject::SELECT);
    }

    /**
     * Begins an INSERT operation
     *
     * @param string $table
     *
     * @return PDOQueryObject
     */
    public function insert(string $table) : PDOQueryObject
    {
        return $this->createQueryObject($table, null, PDOQueryObject::INSERT);
    }

    /**
     * Begins an UPDATE operation
     *
     * @param string $table
     *
     * @return PDOQueryObject
     */
    public function update(string $table) : PDOQueryObject
    {
        return $this->createQueryObject($table, null, PDOQueryObject::UPDATE);
    }

    /**
     * Begins a DELETE operation
     *
     * @param string $table
     *
     * @return PDOQueryObject
     */
    public function delete(string $table) : PDOQueryObject
    {
        return $this->createQueryObject($table, null, PDOQueryObject::DELETE);
    }

    /**
     * Entry point for ad hoc commands (such as 'SHOW TRIGGERS').
     * Depending on the command, may or may not return a result set
     *
     * @param string $sql
     *
     * @return PDOQueryObject
     */
    public function adhoc(string $sql) : PDOQueryObject
    {
        return $this->createQueryObject($sql, null, PDOQueryObject::ADHOC);
    }

    /**
     * Gets the row count for the specified table
     *
     * @param string $table
     *
     * @return int
     * @throws Exception
     */
    public function getTableRowCount(string $table) : int
    {
        return (int)$this->selectFrom($table)->fields('COUNT(*)')->execute()->fetchColumn();
    }

    /**
     * Gets the number of rows that were affected by the last query
     *
     * @return int The number of rows that were affected by the last query.
     */
    public function getRowCount()
    {
        return $this->rowCount;
    }

    /**
     * Gets the last automatic id value that was generated.
     *
     * @param string|null $name Sequence name (only used by certain PDO drivers)
     *
     * @return int The last automatic id generated by the database,
     */
    public function getLastId(string|null $name = null)
    {
        $this->validateConnection();
        return $this->connection->lastInsertId($name);
    }

    /**
     * Throws a DatabaseException with the specified message
     *
     * @param string $message
     */
    public final function throwException(string $message)
    {
        throw new DatabaseException($message);
    }

    public final function __get($var)
    {
        echo "CoreDatabase::Get: <b>$var</b> (property does not exist)<br>";
    }

    public final function __call($function, $args)
    {
        $this->throwException("CoreDatabase::Call: $function (method does not exist)");
    }

    /**
     * Closes the connection
     */
    protected function close()
    {
        $this->connection = null;
    }

    /**
     * Create a series of question mark placeholders.
     *
     * @param int $count The number to create
     * @return string
     */
    protected function createPlaceholders(int $count): string
    {
        return implode(',', array_fill(0, $count, '?'));
    }

    /**
     * Validates that the connection exists.
     *
     * @throws if connection has not been initialized
     */
    protected final function validateConnection()
    {
        if (!$this->connection)
        {
            $this->throwException('A connection does not exist');
        }
    }

    private function connectDemand()
    {
        /* If already a connection, nothing to do */
        if ($this->connection)
        {
            return;
        }

        /**
         * $this->connectionConfig:
         * @obj->prefix (DSN prefix)
         * @obj->host (host server name)
         * @obj->database (database name)
         * @obj->persist (connection persistency)
         * @obj->timeout (connection timeout seconds)
         * @obj->user (user name)
         * @obj->password
         */
        try
        {
            $cc = $this->connectionConfig;
            $dsn = sprintf('%s:host=%s;dbname=%s', $cc->prefix, $cc->host, $cc->database);
            $options =
                [
                    PDO::ATTR_TIMEOUT => (int)$cc->timeout ?: 5,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                    PDO::ATTR_PERSISTENT => $cc->persist,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];

            $this->connection = new PDO($dsn, $cc->user, $cc->password, $options);
        }
        catch (Exception)
        {
            $this->throwException('Database connection could not be established');
        }
    }

    private function createQueryObject(string $table, string|null $alias, int $type) : PDOQueryObject
    {
        try
        {
            $this->connectDemand();
            $obj = new PDOQueryObject($this->connection, $table, $alias, $type, function ($m)
            {
                $this->throwException($m);
            });

            return $obj;
        }
        catch (PDOException $e)
        {
            $this->throwException($e->getMessage());
        }
    }
}
?>