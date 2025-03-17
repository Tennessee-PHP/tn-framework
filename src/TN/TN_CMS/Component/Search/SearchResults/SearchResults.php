<?php

namespace TN\TN_CMS\Component\Search\SearchResults;

use TN\TN_CMS\Model\PageEntry;
use TN\TN_CMS\Model\Search\SearchQuery;
use TN\TN_Core\Attribute\Components\FromQuery;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Model\User\User;

#[Reloadable]
class SearchResults extends HTMLComponent
{
    #[FromQuery] public string $search = '';
    public array $pageEntries = [];

    public function prepare(): void
    {
        if (strlen($this->search < 3)) {
            return;
        }
        SearchQuery::recordSearch($this->search);
        $this->pageEntries = PageEntry::getPageEntries(['search' => $this->search], 0, 100);
    }
}