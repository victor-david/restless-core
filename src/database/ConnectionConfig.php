<?php declare(strict_types=1);
namespace Restless\Database;

/**
* Provides configuration values to access a database
*
* @property string  $database   Name of the database
* @property string  $host       Host name
* @property string  $password   Password to connect
* @property bool    $persist    Persistent connection, default true
* @property string  $prefix     Prefix, default 'mysql'
* @property int     $timeout    Connection timeout, default 5 seconds
* @property string  $user       Name of database user
*/
final class ConnectionConfig
{
  public $database;
  public $host;
  public $password;
  public $persist;
  public $prefix;
  public $timeout;
  public $user;

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