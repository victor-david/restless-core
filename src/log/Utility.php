<?php declare(strict_types=1);
namespace Restless\Log;

/**
 * Provides static utility methods
 */
final class Utility
{
    /**
     * Gets a simple class representation
     *
     * @param mixed $object
     *
     * @return object|string
     */
    public static function getClass(object $object): string
    {
        if (method_exists($object, '__toString'))
        {
            return $object->__toString();
        }
        $class = get_class($object);
        return "[object $class]";
    }

    /**
     * Gets a simple type representation
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function getType($value) : string
    {
        $type = gettype($value);
        return "[$type]";
    }

    /**
     * Returns the JSON representation of a value
     *
     * @param  mixed   $data
     *
     * @return string
     */
    public static function jsonEncode($data): string
    {
        $json = @json_encode($data);
        return ($json !== false) ? $json : 'null';
    }
}
?>