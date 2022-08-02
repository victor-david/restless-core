<?php declare(strict_types=1);
namespace Restless\Core;

use Exception;
use InvalidArgumentException;

/**
* Represents a collection of application objects. This class must be inherited.
*
* This class represents the base class for a collection of applications. It defines
* properties for common and current apps. The key for the common app must be 'common'
*
* @author Victor D. Sandiego
*/
abstract class AppCollection implements AppInterface
{
  /**
  * The fixed key for the common application.
  */
  public const COMMON_KEY = 'common';

  /**
  * @var App
  */
  public $common;

  /**
  * @var App
  */
  public $current;

  /**
  * Adds an application object from the specified row
  *
  * @param array $row
  */
  public function add(array $row)
  {
    if (!isset($row['key']))
    {
      throw new InvalidArgumentException('No key supplied');
    }

    $key = $row['key'];
    if (property_exists($this, $key))
    {
      $this->$key = $this->getApp($row);
    }
  }

  /**
  * Gets the application with the specified id
  *
  * @param int $id
  * @return App
  * @throws Exception
  */
  public function getById(int $id): App
  {
    foreach ($this as $app)
    {
      if ($app->id == $id)
      {
        return $app;
      }
    }
    throw new InvalidArgumentException('Invalid app id');
  }

  /**
  * Gets the application with the specified key
  *
  * @param string $key
  * @return App
  * @throws Exception
  */
  public function getByKey(string $key): App
  {
    foreach ($this as $appKey => $app)
    {
      if ($appKey == $key)
      {
        return $app;
      }
    }
    throw new InvalidArgumentException('Invalid app');
  }

  /**
  * Gets the application with the specified xid
  *
  * @param string $xid
  * @return App
  * @throws Exception
  */
  public function getByXid(string $xid): App
  {
    foreach ($this as $app)
    {
      if ($app->xid == $xid)
      {
        return $app;
      }
    }
    throw new InvalidArgumentException('Invalid app');
  }
}
?>