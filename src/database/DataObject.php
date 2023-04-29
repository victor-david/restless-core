<?php declare(strict_types=1);
namespace Restless\Database;

/**
* Represents a data object.
*
* @abstract
* @property \Restless\Database\CoreDatabasePdo $db
*/
abstract class DataObject implements TransactionInterface
{
  protected const SqlDateFormat = 'Y-m-d H:i:s';

  protected $db;

  protected function __construct(CoreDatabasePdo $db)
  {
    $this->db = $db;
  }

  /**
  * Initializes and returns an instance
  *
  * @param CoreDatabasePdo $db
  * @return static
  */
  public static function create(CoreDatabasePdo $db)
  {
    return new static($db);
  }

  /**
  * Begins a transaction if one if not already in progress.
  */
  public function beginTransaction()
  {
    $this->db->beginTransaction();
  }

  /**
  * Commits a transaction.
  */
  public function commitTransaction()
  {
    $this->db->commitTransaction();
  }

  /**
  * Rolls back a transaction.
  */
  public function rollbackTransaction()
  {
    $this->db->rollbackTransaction();
  }

  /**
  * Gets a \DateTimeImmutable with the current date/time in UTC
  */
  protected function getUtcNow() : \DateTimeImmutable
  {
    return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
  }

  /**
  * Gets the current date/time UTC in a string suitable for the database
  *
  * @param string $format The format or omit for default
  * @return string
  */
  protected function getUtcNowDateStr(string $format = self::SqlDateFormat) : string
  {
    $now = $this->getUtcNow();
    return $now->format($format);
  }

  /**
  * Gets a Unix timestamp for the current date/time
  *
  * @return int
  */
  protected function getUnixTimestamp(): int
  {
    $now = new \DateTime('now', new \DateTimeZone('UTC'));
    return $now->getTimestamp();
  }

  /**
  * Gets a DateTime object for today's date (UTC) at midnight
  *
  * @return \DateTime
  */
  protected function getUnixMidnight(): \DateTime
  {
    return new \DateTime('today midnight', new \DateTimeZone('UTC'));
  }

  /**
  * Gets a Unix timestamp for today's date (UTC) at midnight
  *
  * @return int
  */
  protected function getUnixTimestampMidnight(): int
  {
    return $this->getUnixMidnight()->getTimestamp();
  }

  /**
  * Creates a new object that has only the specified properties.
  *
  * @param array $props A one-dimensional array that lists the desired property names.
  * @return object
  */
  public function createFilteredObj(array $props): object
  {
    $filtered = [];

    foreach($props as $name)
    {
      if (!empty($name) && isset($this->$name))
      {
        $filtered[$name] = $this->$name;
      }
    }
    return (object)$filtered;
  }

  /**
  * Dumps this instance for debugging.
  *
  * After calling this method, the object is no longer usuable
  * because it clears $this->db to avoid a large dump.
  */
  public final function dump()
  {
    $this->prepareForDump($this);
    echo '<pre class="ms-3">';
    print_r($this);
    echo '</pre>';
  }

  protected function prepareForDump(DataObject $obj)
  {
    $obj->db = '(removed for clarity)';

    foreach ($obj as $property => $value)
    {
      if ($value instanceof DataObject)
      {
        $this->prepareForDump($value);
      }

      if (is_array($value))
      {
        for ($k = 0; $k < count($value); $k++)
        {
          if ($value[$k] instanceof DataObject)
          {
            $this->prepareForDump($value[$k]);
          }
        }
      }
    }
  }

  /**
  * Gets the current class name without namespace
  *
  * @return string
  */
  protected function getClass(): string
  {
    $c = get_class($this);
    $p = strrpos($c, '\\');
    if ($p) $c = substr($c, $p + 1);
    return $c;
  }

  /**
  * Throws an exception if an attempt is made to get a non-existent property
  *
  * @param mixed $name
  * @throws Exception
  */
  public function __get($name)
  {
    throw new \UnexpectedValueException(sprintf('Property %s in %s does not exist', $name, $this->getClass()));
  }

  /**
  * Prevents properties from being added.
  */
  public function __set($name, $value)
  {
    throw new \UnexpectedValueException(sprintf('Adding property %s to %s is not allowed', $name, $this->getClass()));
  }
}
?>