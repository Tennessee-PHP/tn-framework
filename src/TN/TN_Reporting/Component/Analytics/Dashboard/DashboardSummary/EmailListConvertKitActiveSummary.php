<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\DashboardSummary;


use TN\TN_Reporting\Model\Analytics\EmailList\EmailListConvertKitActiveEntry;

class EmailListConvertKitActiveSummary extends TypeSummary
{
    public string $analyticsEntryClass = EmailListConvertKitActiveEntry::class;

    public string $chartType = 'line';

    public string $reportKey = 'emailListActive';

    public ?string $title = 'Email List Subscribers';

    public string $compareMethod = 'last';

    public string $compareKey = 'count';

    public array $dialDisplayOptions = [
        'prefix' => '',
        'decimals' => 0
    ];
}