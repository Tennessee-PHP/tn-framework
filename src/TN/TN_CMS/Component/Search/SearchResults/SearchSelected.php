<?php

namespace TN\TN_CMS\Component\Search\SearchResults;

use TN\TN_CMS\Model\Search\SearchQuery;
use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Component\Renderer\Text\Text;

class SearchSelected extends Text {
    #[FromPost] public ?string $search = null;
    #[FromPost] public ?int $pageEntryId = null;
    public function prepare(): void {
        if (!$this->search || !$this->pageEntryId) {
            return;
        }
        $query = SearchQuery::getFromString($this->search);
        $query->recordSelectedResult($this->pageEntryId);
        $this->text = 'success';
    }
}