<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Users\NewLeaguesSyncedEntry;

class NewLeaguesSyncedReport extends TypeReport
{

    /** @var string  */
    public string $analyticsEntryClassName = NewLeaguesSyncedEntry::class;

    /** @var string  */
    public string $reportKey = 'newLeaguesSynced';

    /** @var string  */
    public string $chartType = 'bar';
}
