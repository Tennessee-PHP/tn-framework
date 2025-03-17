<?php

namespace TN\TN_S3Download;

class Module extends \TN\TN_Core\Model\Package\Module
{
    public string $package = \TN\Package::class;
    public string $name = 'TN_S3Download';
}