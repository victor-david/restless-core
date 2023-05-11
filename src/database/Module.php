<?php declare(strict_types=1);
namespace Restless\Database;

/**
* Represents a collection of database routines / operations for a particular purpose.
* This class must be inherited.
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
  * Initializes and returns an instance
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
  * Convenience method
  */
  protected function beginTransaction()
  {
    $this->db->beginTransaction();
  }

  /**
  * Convenience method
  */
  protected function commitTransaction()
  {
    $this->db->commitTransaction();
  }

  /**
  * Convenience method
  */
  protected function rollbackTransaction()
  {
    $this->db->rollbackTransaction();
  }
}
?>