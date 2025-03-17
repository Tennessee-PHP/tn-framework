<?php

namespace TN\TN_CMS\Component\Search\Admin\ListSearchQueries;

use TN\TN_CMS\Model\Search\SearchQuery;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;

#[Page('Search Queries', 'TN_CMS:Search:adminListSearchQueries', 'View search queries by popularity and success.', false)]
#[Reloadable('TN_CMS:Search:adminListSearchQueriesReload')]
class ListSearchQueries extends HTMLComponent
{
    public Pagination $pagination;
    public array $searchQueries;
    public int $minCount;
    public array $minCountOptions = [1, 10, 100, 1000, 10000];
    public string $sortBy;
    public string $sortOrder;

    public function prepare(): void
    {
        $this->sortBy = $_GET['sortby'] ?? '';
        if (!in_array($this->sortBy, ['totalCount', 'query', 'totalSelectedResults', 'selectedRate'])) {
            $this->sortBy = 'totalCount';
        }
        $this->sortOrder = strtoupper($_GET['sortorder'] ?? '');
        if (!in_array($this->sortOrder, ['DESC', 'ASC'])) {
            $this->sortOrder = 'DESC';
        }

        $this->minCount = (int)($_GET['mincount'] ?? 1);

        $search = new SearchArguments(
            conditions: new SearchComparison('`totalCount`', '>', $this->minCount),
            sorters: new SearchSorter($this->sortBy, $this->sortOrder)
        );

        $count = SearchQuery::count($search);
        $this->pagination = new Pagination([
            'itemCount' => $count,
            'itemsPerPage' => 100,
            'search' => $search
        ]);
        $this->pagination->prepare();

        $this->searchQueries = SearchQuery::search($search);

    }
}