<?php

namespace TN\TN_Core\Component\Error;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Component\Title\Title;

class Error extends HTMLComponent
{
    public string $message;
    public function getPageTitleComponent(array $options): ?Title
    {
        return null;
    }
}