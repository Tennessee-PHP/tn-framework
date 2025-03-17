<?php


namespace TN\TN_Core\Component\Pagination;

use TN\TN_Advert\Model\Advert;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchLimit;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorterDirection;
use TN\TN_Core\Model\Request\HTTPRequest;

/**
 * Pagination component
 *
 * @example
 * $search = new SearchArguments(
 * conditions: !empty($this->title) ? new SearchComparison('`title`', 'LIKE', "%{$this->title}%") : [],
 * sorters: new SearchSorter('title', SearchSorterDirection::DESC )
 * );
 *
 * $count = Advert::count($search);
 * $this->pagination = new Pagination([
 * 'itemCount' => $count,
 * 'itemsPerPage' => 20,
 * 'search' => $search
 * ]);
 * $this->pagination->prepare();
 */
class Pagination extends HTMLComponent
{
    public array $pageOptions = [];
    public int $page = 1;
    public int $itemCount = 0;
    public int $itemsPerPage = 10;
    public int $queryStart = 0;
    public string $requestKey = 'page';
    public int $numPages;
    public static int $directionLinkNumber = 3;
    public ?SearchArguments $search = null;

    public function prepare(): void
    {
        $this->page = 1;

        $request = HTTPRequest::get();
        $value = $request->getRequest($this->requestKey);
        if ($value !== null) {
            $this->page = (int)$value;
        }

        $this->numPages = ceil($this->itemCount / $this->itemsPerPage);
        $this->page = min($this->numPages, $this->page);

        $this->queryStart = ($this->page - 1) * $this->itemsPerPage;
        $this->queryStart = max($this->queryStart, 0);

        if ($this->page > static::$directionLinkNumber + 1) {
            $this->pageOptions[] = ['page' => 1, 'active' => false, 'disabled' => false, 'text' => '1 ...'];
        }

        for ($i = $this->page - static::$directionLinkNumber; $i <= $this->page - 1; $i += 1) {
            if ($i < 1) {
                continue;
            }
            $this->pageOptions[] = ['page' => $i, 'active' => false, 'disabled' => false, 'text' => $i];
        }

        $this->pageOptions[] = ['page' => $this->page, 'active' => true, 'disabled' => false, 'text' => $this->page];

        for ($i = $this->page + 1; $i <= $this->numPages && $i <= $this->page + static::$directionLinkNumber; $i += 1) {
            $this->pageOptions[] = ['page' => $i, 'active' => false, 'disabled' => false, 'text' => $i];
        }

        if ($i <= $this->numPages) {
            $this->pageOptions[] = ['page' => $this->numPages, 'active' => false, 'disabled' => false, 'text' => '... ' . $this->numPages];
        }

        if ($this->search) {
            $this->search->limit = new SearchLimit($this->queryStart, $this->itemsPerPage);
        }

    }

}