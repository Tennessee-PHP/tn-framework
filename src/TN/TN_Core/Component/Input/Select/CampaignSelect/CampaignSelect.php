<?php

namespace TN\TN_Core\Component\Input\Select\CampaignSelect;

use TN\TN_Core\Component\Input\Select\Option;
use TN\TN_Core\Component\Input\Select\Select;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Reporting\Model\Campaign\Campaign;

/**
 * select a campaign
 * 
 */
class CampaignSelect extends Select
{
    public string $htmlClass = 'tn-component-select-campaign-select';
    public string $requestKey = 'campaign';

    protected function getOptions(): array
    {
        $options = [];
        $options[] = new Option('', 'All', null, true);

        foreach (Campaign::search(new SearchArguments()) as $campaign) {
            $options[] = new Option($campaign->id, $campaign->key, null);
        }
        return $options;
    }

    protected function getDefaultOption(): mixed
    {
        return $this->options[0];
    }
}
