<?php

namespace TN\TN_Core\Model\_Deprecated\Util;

/**
 * collection of static methods to treat arrays
 *
 * this class is only named with the plural because "Array" is a PHP keyword and can't be used for class/function names
 * @deprecated
 */
class Arrays
{
    /**
     * this safely gets items from indices of associative arrays
     *
     * @param array $array the array to get data from
     * @param string $index the index to look for
     * @param boolean|string $type if false, don't cast. otherwise cast to the type of this string value
     * @param mixed $defaultValue if it doesn't exist, return this
     * @return mixed
     */
    public static function getIndex(array $array, string $index, bool|string $type = false, mixed $defaultValue = 'notset'): mixed
    {
        $value = $defaultValue !== 'notset' ? $defaultValue : false;
        if (isset($array[$index])) {
            $value = $array[$index];
        }
        switch ($type) {
            case 'int':
                return (int)$value;
            case 'string':
                return (string)$value;
            case 'bool':
                return (bool)$value;
            case 'float':
                return (float)$value;
            default:
                return $value;
        }
    }

    /**
     * takes an array, and returns one that only has the keys specified in the keys argument
     *
     * this is super useful for reducing down $_POST or $_GET to only the variables we care about
     * @param array $array
     * @param array $keys
     * @return array
     */
    public static function pluckKeys(array $array, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            if (isset($array[$key])) {
                $out[$key] = $array[$key];
            }
        }
        return $out;
    }
}


?>