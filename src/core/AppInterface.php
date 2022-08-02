<?php declare(strict_types=1);
namespace Restless\Core;

/**
* Describes an instance can supports application definitions
*/
interface AppInterface
{
  /**
  * Gets a single application.
  */
  public function getApp(array $row) : App;
}
?>