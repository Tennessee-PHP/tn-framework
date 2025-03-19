<?php

namespace TN\TN_CMS\Model\Search;

use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;

#[TableName('cms_selected_search_results')]
class SearchSelectedResult implements Persistence
{
    use MySQL;
    use PersistentModel;

    public int $searchQueryId;
    public int $pageEntryId;
    public int $count = 0;

    /**
     * @throws ValidationException
     */
    public static function record(int $searchQueryId, int $pageEntryId): void
    {
        // let's get it, if we already have one
        $records = self::searchByProperties([
            'searchQueryId' => $searchQueryId,
            'pageEntryId' => $pageEntryId
        ]);

        if (count($records) > 0) {
            $record = $records[0];
            $update = [
                'count' => $record->count + 1
            ];
        } else {
            $record = self::getInstance();
            $update = [
                'searchQueryId' => $searchQueryId,
                'pageEntryId' => $pageEntryId,
                'count' => 1
            ];
        }

        $record->update($update);
    }

    /**
     * @param int $searchQueryId
     * @return array
     */
    public static function getCountsForQuery(int $searchQueryId): array
    {
        $records = self::searchByProperties([
            'searchQueryId' => $searchQueryId
        ]);
        $results = [];
        foreach ($records as $record) {
            $results[$record->pageEntryId] = $record->count;
        }
        return $results;
    }
}