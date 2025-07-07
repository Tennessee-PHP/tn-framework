<?php

namespace TN\TN_CMS\Controller;

use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;
use TN\TN_Core\Attribute\Route\Access\Restrictions\Anyone;

class SearchController extends Controller
{
    #[Path('search')]
    #[Component(\TN\TN_CMS\Component\Search\SearchResults\SearchResults::class)]
    #[Anyone]
    public function searchResults(): void {}

    #[Path('staff/search-queries')]
    #[Component(\TN\TN_CMS\Component\Search\Admin\ListSearchQueries\ListSearchQueries::class)]
    #[RoleOnly('pageentries-admin')]
    public function adminListSearchQueries(): void {}

    #[Path('staff/search/queries/clear')]
    #[RoleOnly('pageentries-admin')]
    #[Component(\TN\TN_CMS\Component\Search\Admin\ListSearchQueries\ClearSearchQueries::class)]
    public function adminClearSearchQueries(): void {}

    #[Path('search/selected')]
    #[Anyone]
    #[Component(\TN\TN_CMS\Component\Search\SearchResults\SearchSelected::class)]
    public function searchResultSelected(): void {}
}
