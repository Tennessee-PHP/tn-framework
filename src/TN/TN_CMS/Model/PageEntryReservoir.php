<?php

namespace TN\TN_CMS\Model;

class PageEntryReservoir
{
    /** @var PageEntry[] */
    protected array $pageEntries;

    /** @var int[] these IDs have already been returned */
    protected array $returnedPageEntryIds;

    /** @var string|null  */
    protected ?string $tag;

    /** @var int  */
    protected int $queryPage;

    /** @var array|null */
    protected ?array $contentClasses;

    /** @var int|null */
    protected ?int $excludePageEntryId;

    const PAGE_ENTRIES_PER_QUERY = 100;

    public function __construct(?string $tag = null, ?array $contentClasses = null, ?int $excludePageEntryId = null)
    {
        $this->tag = $tag;
        $this->contentClasses = $contentClasses;
        $this->excludePageEntryId = $excludePageEntryId;
        $this->reset();
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->pageEntries = [];
        $this->returnedPageEntryIds = [];
        $this->queryPage = 0;
    }

    /**
     * returns matching page entries
     * @param int $num
     * @param array|null $contentClasses
     * @param int|null $excludePageEntryId
     * @return array
     */
    public function getPageEntries(int $num, ?array $contentClasses = null, ?int $excludePageEntryId = null): array
    {
        $i = 0;
        $results = [];
        $effectiveExcludeId = $excludePageEntryId ?? $this->excludePageEntryId;

        while ($i <= count($this->pageEntries) && count($results) < $num) {
            if ($i === count($this->pageEntries)) {
                $this->nextPageQuery();
                if ($i === count($this->pageEntries)) {
                    return $results;
                }
            }
            $pageEntry = $this->pageEntries[$i];

            // Check if this page entry should be included
            $shouldInclude = true;

            // Check content class filter
            if (!empty($contentClasses) && !in_array($pageEntry->contentClass, $contentClasses)) {
                $shouldInclude = false;
            }

            // Check exclusion filter
            if ($effectiveExcludeId && $pageEntry->id === $effectiveExcludeId) {
                $shouldInclude = false;
            }

            if ($shouldInclude) {
                $results[] = $pageEntry;
                $this->returnedPageEntryIds[] = $pageEntry->id;
            }
            $i += 1;
        }
        return $results;
    }

    /**
     * @return void
     */
    private function nextPageQuery(): void
    {
        $this->queryPage += 1;
        $num = self::PAGE_ENTRIES_PER_QUERY;
        $start = ($this->queryPage - 1) * $num;
        $filters = [];
        if (!empty($this->contentClasses)) {
            $filters['contentClassOnly'] = $this->contentClasses;
        }
        if ($this->tag) {
            $filters['tag'] = $this->tag;
        }
        if ($this->excludePageEntryId) {
            $filters['excludeId'] = $this->excludePageEntryId;
        }

        foreach (PageEntry::getPageEntries($filters, $start, $num) as $pageEntry) {
            // check we didn't already return it
            if (!in_array($pageEntry->id, $this->returnedPageEntryIds)) {
                $this->pageEntries[] = $pageEntry;
            }
        }
    }
}
