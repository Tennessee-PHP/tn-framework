<?php

namespace TN\TN_Core\Attribute\Components\HTMLComponent;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class RemoveFooter
{
    public function __construct() {}
}
