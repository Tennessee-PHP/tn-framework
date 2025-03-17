<?php

namespace TN\TN_Core\CLI\Components;

use TN\TN_Core\CLI\CLI;
use TN\TN_Core\Component\ComponentMap;

class Map extends CLI
{
    public function run(): void
    {
        try {
            ComponentMap::write();
            $this->green('Component map updated');
        } catch (\Exception $e) {
            $this->red($e->getMessage());
        }
    }
}