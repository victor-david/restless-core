<?php declare(strict_types=1);
namespace Restless\Core;
use Exception;

/**
 * Provides a lightweight object to represent an array of values.
 *
 * This class provides an object that can create properties on the fly, plus
 * various methods to manipulate the properties.
 *
 */
class OpenObject
{
    /**
     * Class constructor
     *
     * @param array $values  An associative array of values that will populate this object.
     */
    public function __construct(array $values)
    {
        foreach($values as $key=>$value)
        {
            $this->$key = $value;
        }
    }

    /**
     * Creates a class instance that has zero values.
     *
     * @return OpenObject
     */
    public static function EmptyRecord(): self
    {
        return new OpenObject([]);
    }

    /**
     * Gets a OpenObject with result values initialized to their starting defaults
     *
     * @return OpenObject
     */
    public static function ResultRecord(): self
    {
        return new OpenObject(array
        (
            'valid' => 0,
            'data'  => self::EmptyRecord(),
            'html'  => null
        ));
    }

    /**
     * Gets an OpenObject from a \stdClass object recursively.
     *
     * @param \stdClass $obj
     *
     * @return OpenObject
     */
    public static function FromStdClass(\stdClass $obj): self
    {
        $result = self::EmptyRecord();
        foreach ($obj as $key => $value)
        {
            if ($value instanceof \stdClass)
                $result->$key = self::FromStdClass($value);
            else
                $result->$key = $value;
        }
        return $result;
    }

    /**
     * Gets a boolean value that indicates if this record is empty
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return ($this->getPropertyCount() == 0);
    }

    /**
     * Gets the count of properties held in this object.
     *
     * @return int
     */
    public function getPropertyCount(): int
    {
        $count = 0;
        foreach ($this as $key => $value)
        {
            $count++;
        }
        return $count;
    }

    /**
     * Throws an exception if this object is empty
     *
     * @param string|null $msg The message to throw if empty, or null for the default.
     *
     * @return OpenObject this instance
     */
    public function throwIfEmpty($msg = null): self
    {
        if ($this->isEmpty())
        {
            if (!$msg) $msg = 'Data returned an empty result (item does not exist)';
            throw new Exception($msg);
        }
        return $this;
    }

    /**
     * Throws an exception if any of the specified properties evaluate to false
     *
     * Pass an array of property names to check. This method throws an
     * exception if a given property evaluates to false with a standard message.
     *
     * Pass a corresponding array of messages to customize the exception
     *
     * @param array $properties
     * @param array|null $messages
     *
     * @return OpenObject this instance
     */
    public function throwIfProperty(array $properties, ?array $messages = null): self
    {
        $idx = 0;
        foreach ($properties as $prop)
        {
            if (!$this->evaluateProperty($prop))
            {
                $msg = "Property [$prop] is missing";
                if (is_array($messages) && !empty($messages[$idx]))
                {
                    $msg = $messages[$idx];
                }
                throw new Exception($msg);
            }
            $idx++;
        }
        return $this;
    }

    /**
     * Gets a boolean value that indicates if the specified property is considered valid.
     *
     * @param mixed $name
     *
     * @return bool
     */
    protected function evaluateProperty($name): bool
    {
        return ($this->$name) ? true : false;
    }

    /**
     * Gets a property value of this record
     *
     * Normally, you simply use $obj->propName to get the value of a property, but sometimes a OpenObject gets populated
     * with values from ouside the framework. In those cases, the $obj->propName may be syntactically incorrect.
     * This method provides a manner to retreive those property values, example: $obj->get('property-with-dashes');
     *
     * @param string $property The name of the property
     *
     * @return string The value of the property
     */
    public function get($property)
    {
        return $this->$property;
    }

    /**
     * Sets specified properties to null if they evaluate as falsy
     *
     * @param array $properties
     *
     * @return OpenObject
     */
    public function setFalsyToNull(array $properties): self
    {
        foreach ($properties as $prop)
        {
            if (!$this->$prop)
            {
                $this->$prop = null;
            }
        }
        return $this;
    }

    /**
     * Creates a new OpenObject that has only the specified properties
     *
     * @param array $properties A one-dimensional array that lists the desired property names
     *
     * @return OpenObject
     */
    public function createFiltered($properties): self
    {
        if (!is_array($properties)) $properties = array();

        $filtered = array();

        foreach($properties as $name)
        {
            if (!empty($name) && isset($this->$name))
            {
                $filtered[$name] = $this->$name;
            }
        }

        return new OpenObject($filtered);
    }

    /**
     * Transforms all properties of this object using a callback method.
     *
     * This method drills into properties that are OpenObject, but not into arrays or other objects.
     *
     * @param callable $callback callback that receives each property value and returns a transformed value.
     * @param array $excluded property names to exclude from the transform (default [])
     *
     * @return OpenObject
     */
    public function transform(callable $callback, array $excluded = []): self
    {
        foreach ($this as $key => $value)
        {
            if (!in_array($key, $excluded))
            {
                if ($value instanceof OpenObject)
                {
                    $value->transform($callback);
                }
                else if (!is_array($value) && !is_object($value))
                {
                    $this->$key = $callback($value);
                }
            }
        }
        return $this;
    }

    /**
     * Transforms all properties of this instance via trim
     * and removal of duplicate spaces.
     *
     * @param array $excluded property names to exclude from the transform (default [])
     *
     * @return OpenObject;
     */
    public function trim(array $excluded = []): self
    {
        return $this->transform(function($value)
        {
            return preg_replace('!\s+!', ' ', trim($value));
        }, $excluded);
    }

    /**
     * Transforms all properties of this instance via trim
     * and removal of ALL spaces.
     *
     * @param array $excluded property names to exclude from the transform (default [])
     *
     * @return OpenObject;
     */
    public function trimAll(array $excluded = []): self
    {
        return $this->transform(function($value)
        {
            return str_replace(' ', '', trim($value));
        }, $excluded);
    }

    /**
     * Transforms all properties of this instance via htmlentities.
     *
     * @param array $excluded property names to exclude from the transform (default [])
     * @return OpenObject
     */
    public function htmlize(array $excluded = []): self
    {
        return $this->transform(function($value)
        {
           return htmlentities($value);
        }, $excluded);
    }

    /**
     * Transforms all properties of this instance via strip_tags.
     *
     * @param array $excluded property names to exclude from the transform (default [])
     * @return OpenObject
     */
    public function stripTags(array $excluded = []): self
    {
        return $this->transform(function($value)
        {
            return strip_tags($value);
        }, $excluded);
    }

    /**
     * Returns an array from the properties of this instance.
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = array();
        foreach ($this as $key => $value)
        {
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * Merges the properties of the specified object into this instance.
     *
     * @param object $obj An object to merge.
     *
     * @return OpenObject this instance
     */
    public function merge(object $obj): self
    {
        foreach($obj as $key => $value)
        {
           $this->$key = $value;
        }
        return $this;
    }

    /**
     * Displays this object.
     *
     * This method outputs the contents of this object surrounded by **pre** tags.
     * Used for debugging.
     *
     * @param bool $full true = Full display using print_r; false = custom display
     */
    public function display($full = false)
    {
        $text = ($full) ? print_r($this, true) : $this->createDumpText(0, $this);
        echo sprintf('<pre>%s</pre>', $text);
    }

    private function createDumpText(int $level, object $obj, $varName = null)
    {
        $pad = ($level * 2) + 2;
        $mainPad = str_pad('', $pad, ' ');
        $propPad = str_pad('', $pad + 2, ' ');

        $output = sprintf("%s%s%s\n%s{\n", $mainPad, ($varName) ? "$varName => " : null, get_class($obj), $mainPad);
        $vars = get_object_vars($obj);
        foreach ($vars as $var => $value)
        {
            if (is_object($value))
            {
                $output .= $this->createDumpText($level + 1, $value, $var);
            }
            else
            {
                $output .= sprintf("%s%s => %s\n", $propPad, $var, $value);
            }
        }
        $output .= sprintf("%s}\n", $mainPad);
        return $output;
    }

    public function __get ($name)
    {
        return null;
    }

    public function __toString()
    {
        return sprintf('%s - You probably meant to access a property value', __CLASS__);
    }
}
?>