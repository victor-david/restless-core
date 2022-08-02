<?php declare(strict_types=1);
namespace Restless\Core;

/**
* Represents a single application.
*
* This class defines properties that all applications must implement.
*
* @abstract
* @property int     $id   Unique integer id
* @property string  $xid  Unique string id
* @property string  $key  Unique app key, maps to app directories
*/
abstract class App extends StrictObject
{
  public $id;
  public $xid;
  public $key;
}
?>