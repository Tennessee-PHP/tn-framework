<?php

namespace TN\TN_CMS;

class Module extends \TN\TN_Core\Model\Package\Module
{
    public string $package = \TN\Package::class;
    public string $name = 'TN_CMS';
}