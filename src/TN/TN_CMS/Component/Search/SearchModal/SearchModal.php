<?php

namespace TN\TN_CMS\Component\Search\SearchModal;

use TN\TN_CMS\Component\Search\SearchResults\SearchResults;
use \TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Model\User\User;

class SearchModal extends HTMLComponent
{
    public SearchResults $searchResults;
    public bool $canEditResults;

    public function prepare(): void
    {
        $this->searchResults = new SearchResults();
        $this->searchResults->prepare();
        $this->canEditResults = User::getActive()->hasRole('pageentries-admin');
    }
}