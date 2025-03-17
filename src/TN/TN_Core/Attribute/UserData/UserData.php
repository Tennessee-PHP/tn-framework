<?php

namespace TN\TN_Core\Attribute\UserData;

#[\Attribute(\Attribute::TARGET_CLASS)]
class UserData
{
    /**
     * constructor
     * @param string $modelName
     */
    public function __construct(
        public string $modelName
    )
    {
    }
}