<?php

namespace TN\TN_Core\Model\PersistentModel\Search;

class SearchArguments
{
    /** @var SearchCondition[] */
    public array $conditions = [];
    /** @var SearchSorter[] */
    public array $sorters = [];
    public ?SearchLimit $limit = null;

    /**
     * @param SearchCondition[]|SearchCondition $conditions
     * @param SearchSorter[]|SearchSorter $sorters
     * @param SearchLimit|null $limit
     */
    public function __construct(array|SearchCondition $conditions = [], array|SearchSorter $sorters = [], ?SearchLimit $limit = null) {
        $this->conditions = is_array($conditions) ? $conditions : [$conditions];
        $this->sorters = is_array($sorters) ? $sorters : [$sorters];
        $this->limit = $limit;
    }

    public function getCacheIdentifier(): string
    {
        return md5(serialize($this));
    }
}