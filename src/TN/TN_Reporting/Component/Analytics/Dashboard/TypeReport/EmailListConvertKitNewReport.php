<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\EmailList\EmailListConvertKitNewEntry;

class EmailListConvertKitNewReport extends TypeReport
{
    /** @var string  */
    public string $analyticsEntryClassName = EmailListConvertKitNewEntry::class;

    /** @var string  */
    public string $reportKey = 'emailListNew';

    public string $chartType = 'bar';
}