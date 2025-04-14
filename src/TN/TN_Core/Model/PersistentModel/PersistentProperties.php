<?php

namespace TN\TN_Core\Model\PersistentModel;

use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\AutoIncrement;
use TN\TN_Core\Attribute\Relationships\ChildrenClass;
use TN\TN_Core\Attribute\Relationships\ParentObject;

/**
 * gets an instance of a class, first querying the stack for an extended class
 */
trait PersistentProperties
{
    protected function loadPropertyValue(string $property, mixed $value): mixed
    {
        // get a representation of the reflection property $property on $this
        try {
            $reflectionProperty = new \ReflectionProperty($this, $property);
        } catch (\ReflectionException) {
            return $value;
        }

        // get the type of the reflection property; remove the ? if it is nullable
        $type = $reflectionProperty->getType()?->getName();
        $type = str_replace('?', '', $type);

        if ($value === null) {
            return null;
        }

        // if the type is one native to PHP, return it
        if (in_array($type, ['int', 'string', 'float', 'bool'])) {
            return $value;
        }

        if ($type === 'datetime') {
            try {
                return new \DateTime($value);
            } catch (\Exception) {
                return new \DateTime();
            }
        }

        if ($type === 'array') {
            return json_decode($value, true);
        }

        // let's try to get a reflection class of $type and see if it's an enum
        try {
            $reflectionClass = new \ReflectionClass($type);
            if ($reflectionClass->isEnum()) {
                return $type::from($value);
            }
        } catch (\ReflectionException) {
            return $value;
        }

        return $value;
    }

    /**
     * casts values so they are ready to go into mysql
     * @param mixed $value
     * @return mixed
     */
    protected function savePropertyValue(mixed $value): mixed
    {
        // if $value is a type of enum, get its value
        if ($value instanceof \UnitEnum) {
            return $value->value;
        }

        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if ($value === null) {
            return null;
        }

        return is_bool($value) ? (int)$value : $value;
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
