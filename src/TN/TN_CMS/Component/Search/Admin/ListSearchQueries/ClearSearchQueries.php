<?php

namespace TN\TN_CMS\Component\Search\Admin\ListSearchQueries;

use TN\TN_CMS\Model\Search\SearchQuery;
use TN\TN_CMS\Model\Search\SearchQueryDayCount;
use TN\TN_CMS\Model\Search\SelectedSearchResult;
use TN\TN_Core\Component\Renderer\Text\Text;

class ClearSearchQueries extends Text
{
    public function prepare(): void
    {
        if (isset($_POST['confirm'])) {
            SearchQuery::batchEraseAll();
            SearchQueryDayCount::batchEraseAll();
            SelectedSearchResult::batchEraseAll();
            $this->text = 'Search queries have been cleared.';
        } else {
            $this->text = 'Clearing of search queries was not confirmed.';
        }
    }
}