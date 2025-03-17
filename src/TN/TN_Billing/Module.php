<?php

namespace TN\TN_Billing;

class Module extends \TN\TN_Core\Model\Package\Module
{
    public string $package = \TN\Package::class;
    public string $name = 'TN_Billing';
}