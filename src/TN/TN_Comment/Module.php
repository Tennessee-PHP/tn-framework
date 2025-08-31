<?php

namespace TN\TN_Comment;

class Module extends \TN\TN_Core\Model\Package\Module
{
    public string $package = \TN\Package::class;
    public string $name = 'TN_Comment';
    public array $moduleDependencies = [
        \TN\TN_Core\Module::class
    ];
}
