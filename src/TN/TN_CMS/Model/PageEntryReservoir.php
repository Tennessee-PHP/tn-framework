<?php

namespace TN\TN_CMS\Model;

class PageEntryReservoir {
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

    const PAGE_ENTRIES_PER_QUERY = 100;

    public function __construct(?string $tag = null, ?array $contentClasses = null)
    {
        $this->tag = $tag;
        $this->contentClasses = $contentClasses;
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
     * @return array
     */
    public function getPageEntries(int $num, ?array $contentClasses = null): array
    {
        $i = 0;
        $results = [];
        while ($i <= count($this->pageEntries) && count($results) < $num) {
            if ($i === count($this->pageEntries)) {
                $this->nextPageQuery();
                if ($i === count($this->pageEntries)) {
                    return $results;
                }
            }
            $pageEntry = $this->pageEntries[$i];
            if (empty($contentClasses) || in_array($pageEntry->contentClass, $contentClasses)) {
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

        foreach (PageEntry::getPageEntries($filters, $start, $num) as $pageEntry) {
            // check we didn't already return it
            if (!in_array($pageEntry->id, $this->returnedPageEntryIds)) {
                $this->pageEntries[] = $pageEntry;
            }
        }
    }
}