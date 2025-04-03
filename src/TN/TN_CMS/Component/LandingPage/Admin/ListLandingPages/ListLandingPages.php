<?php

namespace TN\TN_CMS\Component\LandingPage\Admin\ListLandingPages;

use TN\TN_CMS\Model\LandingPage;
use TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;

#[Page('List Landing Pages', 'List the landing pages on the website', false)]
#[Route('TN_CMS:LandingPage:adminListLandingPages')]
#[Reloadable]
class ListLandingPages extends HTMLComponent
{
    public array $landingPages;
    public Pagination $pagination;
    public int $stateDraft;
    public int $statePublished;

    public function prepare(): void
    {
        $search = new SearchArguments(sorters: new SearchSorter('publishedTs', 'DESC'));
        $count = LandingPage::count($search);
        $this->pagination = new Pagination([
            'itemCount' => $count,
            'itemsPerPage' => 20,
            'search' => $search
        ]);
        $this->pagination->prepare();
        $this->landingPages = LandingPage::search($search);
        $this->stateDraft = LandingPage::STATE_DRAFT;
        $this->statePublished = LandingPage::STATE_PUBLISHED;
    }
}