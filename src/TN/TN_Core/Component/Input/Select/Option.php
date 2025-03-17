<?php

namespace TN\TN_Core\Component\Input\Select;

/**
 * an option for a select
 *
 */
class Option
{
    public function __construct(
        /** @var string|int stringable key to be used in html value, get vars etc */
        public string|int $key,

        /** @var string label to show to the user as selectable */
        public string     $label,

        /** @var mixed optional representation of an object */
        public mixed      $object,

        /** @var bool whether this option represents all other possible options */
        public bool       $all = false,

        /** @var bool whether this option represents none of the other possible options */
        public bool       $none = false
    )
    {
    }
}