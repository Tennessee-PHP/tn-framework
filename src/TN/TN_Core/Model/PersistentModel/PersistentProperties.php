<?php

namespace TN\TN_Core\Model\PersistentModel;

use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\AutoIncrement;
use TN\TN_Core\Attribute\Relationships\ChildrenClass;
use TN\TN_Core\Attribute\Relationships\ParentObject;
use TN\TN_Core\Attribute\Encrypt;
use TN\TN_Core\Service\Encryption;

/**
 * gets an instance of a class, first querying the stack for an extended class
 */
trait PersistentProperties
{
    protected function loadPropertyValue(string $property, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        // get a representation of the reflection property $property on $this
        try {
            $reflectionProperty = new \ReflectionProperty($this, $property);
        } catch (\ReflectionException) {
            return $value;
        }

        // First check if property was encrypted and needs decryption
        $encryptAttributes = $reflectionProperty->getAttributes(Encrypt::class);
        if (!empty($encryptAttributes) && $value !== null) {
            $value = Encryption::getInstance()->decrypt($value);
        }

        // get the type of the reflection property; remove the ? if it is nullable
        $type = $reflectionProperty->getType()?->getName();
        $type = str_replace('?', '', $type);

        // Then handle all type conversions
        if (in_array($type, ['int', 'string', 'float', 'bool'])) {
            // No conversion needed for basic types
        } else if ($type === 'DateTime') {
            try {
                // Always load DateTime values from database as UTC since we store them without timezone info
                $value = new \DateTime($value, new \DateTimeZone('UTC'));
            } catch (\Exception) {
                $value = new \DateTime('now', new \DateTimeZone('UTC'));
            }
        } else if ($type === 'array') {
            // If it's already an array, keep it as is
            // If it's a string, try to JSON decode it
            if (is_string($value)) {
                $value = json_decode($value, true);
            }
        } else {
            // Check if it's an enum
            try {
                $reflectionClass = new \ReflectionClass($type);
                if ($reflectionClass->isEnum()) {
                    $value = $type::from($value);
                }
            } catch (\ReflectionException) {
                // If we can't reflect the type, just keep the value as is
            }
        }

        return $value;
    }

    /**
     * casts values so they are ready to go into mysql
     * @param mixed $value
     * @return mixed
     */
    protected function savePropertyValue(string $property, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        // First handle all type conversions
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        } else if ($value instanceof \UnitEnum) {
            $value = $value->name;
        } else if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d H:i:s');
        } else if (is_array($value)) {
            $value = json_encode($value);
        } else if (is_bool($value)) {
            $value = (int)$value;
        }

        // Finally, check if property should be encrypted
        try {
            $reflectionProperty = new \ReflectionProperty($this, $property);
            $encryptAttributes = $reflectionProperty->getAttributes(Encrypt::class);
            if (!empty($encryptAttributes)) {
                return Encryption::getInstance()->encrypt($value);
            }
        } catch (\ReflectionException) {
            // If we can't reflect the property, just return the converted value
        }

        return $value;
    }

    /**
     * gets properties that need to be saved to the DB
     * @return array
     */
    protected static function getPersistentProperties(): array
    {
        $class = new \ReflectionClass(get_called_class());
        $persistentProperties = [];
        foreach ($class->getProperties() as $property) {
            $propertyName = $property->getName();
            $attributes = array_merge(
                $property->getAttributes(Impersistent::class),
                $property->getAttributes(AutoIncrement::class),
                $property->getAttributes(ChildrenClass::class),
                $property->getAttributes(ParentObject::class)
            );
            if (count($attributes) === 0 && !$property->isStatic()) {
                $persistentProperties[] = $propertyName;
            }
        }

        return $persistentProperties;
    }
}
