<?php

namespace TN\TN_CMS\Component\PageEntry\Admin\ListPageEntries;

use TN\TN_CMS\Component\PageEntry\Admin\EditPageEntry\EditPageEntry;
use TN\TN_CMS\Model\Content;
use TN\TN_CMS\Model\PageEntry;
use TN\TN_CMS\Model\Search\SearchQuery;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use \TN\TN_Core\Attribute\Components\Route;

#[Page('Site Content: Tagging, Weight &amp; Search', '', 'For each public-facing page on the website, edit the page details that relate to SEO.', false)]
#[Route('TN_CMS:PageEntry:adminListPageEntries')]
#[Reloadable]
class ListPageEntries extends HTMLComponent
{
    public string $pathFilter = '';
    public string $titleFilter = '';
    public string $tagFilter = '';
    public string $searchFilter = '';
    public bool $onlyNoTags = false;
    public bool $onlyNoThumbnail = false;
    public array $pageEntries = [];
    public int $pageEntriesCount = 0;
    public array $contentTypeLabels = [];
    public array $contentTypeFilters = [];
    public array $contentClassExclude = [];
    public EditPageEntry $editPageEntry;
    public Pagination $pagination;
    public ?SearchQuery $searchQuery = null;

    public function prepare(): void
    {
        $this->editPageEntry = new EditPageEntry();
        $this->editPageEntry->prepare();
        $this->pathFilter = $_GET['filter_path'] ?? '';
        $this->titleFilter = $_GET['filter_title'] ?? '';
        $this->tagFilter = $_GET['filter_tag'] ?? '';
        $this->searchFilter = $_GET['filter_search'] ?? '';
        $this->onlyNoTags = $_GET['filter_notags'] == '1';
        $this->onlyNoThumbnail = $_GET['filter_nothumbnail'] == '1';

        $this->contentTypeFilters = [];
        $this->contentTypeLabels = [];
        $contentType = PageEntry::getReadableContentType();
        $this->contentTypeLabels[PageEntry::class] = $contentType;
        if (!isset($_GET[$contentType . '_filter']) || (string)$_GET[$contentType . '_filter'] === '1') {
            $this->contentTypeFilters[] = $contentType;
        } else {
            $this->contentClassExclude[] = PageEntry::class;
        }

        foreach (Content::getContentClasses() as $class) {
            $contentType = $class::getReadableContentType();
            $this->contentTypeLabels[$class] = $contentType;
            $getField = str_replace(" ", "_", $contentType) . '_filter';
            if (!isset($_GET[$getField]) || (string)$_GET[$getField] === '1') {
                $this->contentTypeFilters[] = $contentType;
            } else {
                $this->contentClassExclude[] = $class;
            }
        }

        if (!empty($this->searchFilter)) {
            $this->searchQuery = SearchQuery::getFromString($this->searchFilter);
        }

        $search = new SearchArguments();
        $filters = [
            'path' => $this->pathFilter,
            'title' => $this->titleFilter,
            'tag' => $this->tagFilter,
            'search' => $this->searchFilter,
            'onlyNoTags' => $this->onlyNoTags,
            'onlyNoThumbnail' => $this->onlyNoThumbnail,
            'contentClassExclude' => $this->contentClassExclude
        ];
        $count = count(PageEntry::getPageEntries($filters, 0, 200));
        $this->pagination = new Pagination([
            'itemCount' => $count,
            'itemsPerPage' => 20,
            'search' => $search
        ]);
        $this->pagination->prepare();
        $this->pageEntries = PageEntry::getPageEntries($filters, $this->pagination->queryStart, $this->pagination->itemsPerPage);
        $this->pageEntriesCount = count($this->pageEntries);
        if ($this->searchQuery) {
            $selectedCounts = $this->searchQuery->getSelectedPageEntryCounts();
            foreach ($this->pageEntries as &$pageEntry) {
                if (isset($selectedCounts[$pageEntry->id])) {
                    $pageEntry->searchSelectedCount = $selectedCounts[$pageEntry->id];
                    $pageEntry->searchSelectedRate = $selectedCounts[$pageEntry->id] / $this->searchQuery->totalCount;
                }
            }
        }
    }
}