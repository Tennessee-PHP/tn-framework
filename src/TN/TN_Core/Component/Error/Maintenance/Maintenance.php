<?php

namespace TN\TN_Core\Component\Error\Maintenance;

use TN\TN_Core\Attribute\Components\HTMLComponent\RemoveNavigation;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Component\Title\Title;

#[RemoveNavigation]
class Maintenance extends HTMLComponent
{
    public string $message;
    public function getPageTitleComponent(array $options): ?Title
    {
        return null;
    }
}
