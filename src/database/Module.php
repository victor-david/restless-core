<?php declare(strict_types=1);
namespace Restless\Database;

/**
 * Represents a collection of database routines / operations for a particular purpose.
 *
 * @abstract
 */
abstract class Module
{
    /**
     * @var \Restless\Database\CoreDatabasePdo
     */
    protected $db;

    protected function __construct(CoreDatabasePdo $db)
    {
        $this->db = $db;
    }

    /**
     * Creates and returns an instance
     *
     * @param CoreDatabasePdo $db
     *
     * @return static
     */
    public static function create(CoreDatabasePdo $db): static
    {
        return new static($db);
    }

    /**
     * Convenience method to begin a transaction.
     */
    protected function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    /**
     * Convenience method to commit a transaction
     */
    protected function commitTransaction()
    {
        $this->db->commitTransaction();
    }

    /**
     * Convenience method to rollback a transaction
     */
    protected function rollbackTransaction()
    {
        $this->db->rollbackTransaction();
    }
}
?>