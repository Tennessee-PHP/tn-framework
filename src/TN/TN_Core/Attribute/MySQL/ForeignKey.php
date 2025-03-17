<?php

namespace TN\TN_Core\Attribute\MySQL;

use TN\TN_Core\Model\Package\Stack;

/**
 * property is part of the primary key
 * 
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ForeignKey
{
    public string $table;
    public string $column;

    /**
     * @throws \ReflectionException
     */
    public function __construct(public string $foreignClassName)
    {
        $this->foreignClassName = Stack::resolveClassName($foreignClassName);
        $this->table = ($this->foreignClassName)::getTableName();

        $reflection = new \ReflectionClass($this->foreignClassName);
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $attributes = $property->getAttributes();
            foreach ($attributes as $attribute) {
                if ($attribute->getName() == PrimaryKey::class) {
                    $this->column = $property->getName();
                    break;
                }
            }
        }
    }
}