<?php

namespace TN\TN_Core\Component\Provider\Meta\MetaPixel;

use TN\TN_Core\Component\HTMLComponent;

class MetaPixel extends HTMLComponent {
    public array $events = [];

    public function event(string $event, array $args = []): void
    {
        $eventObj = new \stdClass;
        $eventObj->event = $event;
        $eventObj->args = $args;
        $this->events[] = $eventObj;
    }
}