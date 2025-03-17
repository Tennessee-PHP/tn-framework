<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;
use TN\TN_Reporting\Model\Analytics\EmailList\EmailListConvertKitActiveEntry;

class EmailListConvertKitActiveReport extends TypeReport
{

    /** @var string  */
    public string $analyticsEntryClassName = EmailListConvertKitActiveEntry::class;

    /** @var string  */
    public string $reportKey = 'emailListActive';
}