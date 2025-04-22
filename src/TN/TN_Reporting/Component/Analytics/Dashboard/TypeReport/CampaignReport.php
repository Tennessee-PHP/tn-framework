<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Campaign\CampaignDailyEntry;

class CampaignReport extends TypeReport
{

    /** @var string  */
    public string $analyticsEntryClassName = CampaignDailyEntry::class;

    /** @var string  */
    public string $reportKey = 'campaign';

    /** @var string  */
    public string $chartType = 'bar';
}
