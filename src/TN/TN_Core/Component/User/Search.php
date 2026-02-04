<?php

namespace TN\TN_Core\Component\User;

use TN\TN_Core\Model\User\User;
use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Component\Renderer\JSON\JSON;

class Search extends JSON
{
    #[FromQuery] public string $term = '';
    
    public function prepare(): void
    {
        $this->data = User::autocomplete($this->term);
    }
}


