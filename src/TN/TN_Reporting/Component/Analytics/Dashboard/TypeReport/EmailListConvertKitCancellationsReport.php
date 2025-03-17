<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\EmailList\EmailListConvertKitCancellationsEntry;

class EmailListConvertKitCancellationsReport extends TypeReport
{
    /** @var string  */
    public string $analyticsEntryClassName = EmailListConvertKitCancellationsEntry::class;

    /** @var string  */
    public string $reportKey = 'emailListCancellations';

    public string $chartType = 'bar';
}