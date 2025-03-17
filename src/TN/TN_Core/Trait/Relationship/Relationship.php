<?php

namespace TN\TN_Core\Trait\Relationship;
use TN\TN_Core\Attribute\Relationships\ChildrenClass;
use TN\TN_Core\Attribute\Relationships\ParentClass;
use TN\TN_Core\Attribute\Relationships\ParentId;
use TN\TN_Core\Attribute\Relationships\ParentObject;

/**
 * parent of another persistent object
 * 
 */
trait Relationship
{
    protected static function getDescendentTree(int $depth = 0): array
    {
        $tree = [
            'class' => __CLASS__,
            'parentProperties' => null,
            'children' => []
        ];

        $class = new \ReflectionClass(__CLASS__);

        // let's see if this has a parent class
        $parentClassAttributes = $class->getAttributes(ParentClass::class);
        if (!empty($parentClassAttributes)) {
            $tree['parentProperties'] = [
                'class' => $parentClassAttributes[0]->newInstance()->class
            ];
            foreach ($class->getProperties() as $property) {
                $parentIdAttributes = $property->getAttributes(ParentId::class);
                if (!empty($parentIdAttributes)) {
                    $tree['parentProperties']['parentIdProperty'] = $property->getName();
                }
                $parentObjectAttributes = $property->getAttributes(ParentObject::class);
                if (!empty($parentObjectAttributes)) {
                    $tree['parentProperties']['parentObjectProperty'] = $parentObjectAttributes->getName();
                }
            }
        }

        if ($depth !== 1) {
            // go further and try to get the descendent tree for this class also
            foreach ($class->getProperties() as $property) {
                $childrenClassAttributes = $property->getAttributes(ChildrenClass::class);
                if (!empty($childrenClassAttributes)) {
                    $tree['children'][] = $childrenClassAttributes[0]->newInstance()->class::getDescendentTree($depth > 0 ? $depth - 1 : $depth);
                }
            }

        }

        return $tree;
    }
}