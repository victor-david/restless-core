<?php declare(strict_types=1);
namespace Restless\Core;
use Exception;

/**
 * Represents the base class for a strict object. This class must be inherited.
 *
 * This class extends OpenObject, but overrides and adds methods so that this object cannot
 * implement properties on the fly; classes that derive from StrictObject must declare the properties
 * they have. Incoming values from the array passed to the constructor will only be assigned if the corresponding
 * property exists; no properties will be auto-created. Attempted access to undeclared properties throws an exception.
 */
abstract class StrictObject extends OpenObject
{
    /**
     * Overrides the parent constructor to only assign property values only if the property already exists
     *
     * @param array $values An associative array of values that will populate this object.
     */
    public function __construct(array $row)
    {
        foreach ($row as $prop => $value)
        {
            if (property_exists($this, $prop))
            {
                $this->$prop = $value;
            }
        }
    }

    /**
     * Overrides the parent method to count only those properties that are set.
     *
     * @return int
     */
    public function getPropertyCount(): int
    {
        $count = 0;
        foreach ($this as $key => $value)
        {
            if (isset($this->$key)) $count++;
        }
        return $count;
    }

    /**
     * Overrides the parent method to provide different logic when evaluating a property
     *
     * @param string $name
     * @return bool
     */
    protected function evaluateProperty($name): bool
    {
        return (property_exists($this, $name) && isset($this->$name));
    }

    /**
     * Gets a boolean value that indicates if the specified property exists
     *
     * @param mixed $name
     * @return bool
     */
    public function hasProperty($name): bool
    {
        if (!$name) return false;
        return property_exists($this, $name);
    }

    /**
     * Throws an exception if an attempt is made to get a non-existent property
     *
     * @param mixed $name
     * @throws Exception
     */
    public function __get($name)
    {
        throw new Exception("Property [$name] does not exist (get)");
    }

    /**
     * Throws an exception if an attempt is made to set a non-existent property
     *
     * @param mixed $name
     * @param mixed $value
     * @throws Exception
     */
    public function __set($name, $value)
    {
        throw new Exception("Property [$name] does not exist (set)");
    }
}
?>