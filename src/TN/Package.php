<?php

namespace TN;

class Package extends \TN\TN_Core\Model\Package\Package
{
    public string $name = 'TN';
    public array $modules = [
        \TN\TN_Core\Module::class
    ];
}