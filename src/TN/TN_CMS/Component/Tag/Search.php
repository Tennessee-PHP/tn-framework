<?php

namespace TN\TN_CMS\Component\Tag;

use TN\TN_CMS\Model\Tag\Tag;
use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Component\Renderer\JSON\JSON;

class Search extends JSON {
    #[FromQuery] public string $term = '';
    public function prepare(): void
    {
        $this->data = Tag::autocomplete($this->term);
    }
}