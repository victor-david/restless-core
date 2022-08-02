<?php declare(strict_types=1);
namespace Restless\Database;

/**
* Interface Transaction interface
*/
interface TransactionInterface
{
  /**
  * Begins a transaction if one if not already in progress.
  */
  public function beginTransaction();

  /**
  * Commits a transaction.
  */
  public function commitTransaction();

  /**
  * Rolls back a transaction.
  */
  public function rollbackTransaction();
}
?>