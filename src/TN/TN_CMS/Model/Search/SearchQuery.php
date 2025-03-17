<?php

namespace TN\TN_CMS\Model\Search;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchLimit;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;

#[TableName('cms_search_queries')]
class SearchQuery implements Persistence
{
    use MySQL;
    use PersistentModel;

    public string $query;
    public int $totalCount;
    public int $totalSelectedResults;
    public float $selectedRate;

    protected static function sanitizeQuery(string $query): string
    {
        return trim(strtolower($query));
    }

    /**
     * @param string $query
     * @return SearchQuery|null
     */
    public static function getFromString(string $query): ?SearchQuery
    {
        $query = self::sanitizeQuery($query);
        $records = self::searchByProperty('query', strtolower($query));
        return count($records) ? $records[0] : null;
    }

    /**
     * gets search queries
     * @param int $minCount minimum number of counts total
     * @param string $sortBy totalCount, lastWeek, selectedRate
     * @param string $sortOrder
     * @param int $start
     * @param int $num
     * @return array
     */
    public static function getSearchQueries(int $minCount, string $sortBy, string $sortOrder, int $start, int $num): array
    {
        return static::search(new SearchArguments(
            new SearchComparison('`totalCount`', '>', $minCount),
            new SearchSorter($sortBy, $sortOrder),
            new SearchLimit($start, $num)
        ));
    }

    /**
     * @param string $query
     * @return void
     * @throws ValidationException
     */
    public static function recordSearch(string $query): void
    {
        $query = self::sanitizeQuery($query);
        $record = self::getFromString($query);
        if (!$record) {
            $record = self::getInstance();
            $update = [
                'query' => $query,
                'totalCount' => 1,
                'totalSelectedResults' => 0,
                'selectedRate' => 0.0
            ];
        } else {
            $update = [
                'totalCount' => $record->totalCount + 1,
                'selectedRate' => ($record->totalSelectedResults / ($record->totalCount + 1))
            ];
        }
        $record->update($update);

        // now we also need to record the day count
        SearchQueryDayCount::recordSearch($record->id);
    }

    /**
     * @param int $pageEntryId
     * @return void
     * @throws ValidationException
     */
    public function recordSelectedResult(int $pageEntryId): void
    {
        $this->update([
            'totalSelectedResults' => $this->totalSelectedResults + 1,
            'selectedRate' => (($this->totalSelectedResults + 1) / ($this->totalCount))
        ]);
        SelectedSearchResult::record($this->id, $pageEntryId);
    }

    /**
     * @return array pageEntryId => count
     */
    public function getSelectedPageEntryCounts(): array
    {
        return SelectedSearchResult::getCountsForQuery($this->id);
    }
}