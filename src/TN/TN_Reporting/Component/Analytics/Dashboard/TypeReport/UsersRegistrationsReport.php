<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Users\UsersRegistrationsEntry;

class UsersRegistrationsReport extends TypeReport
{

    /** @var string  */
    public string $analyticsEntryClassName = UsersRegistrationsEntry::class;

    /** @var string  */
    public string $reportKey = 'userRegistrations';

    /** @var string  */
    public string $chartType = 'bar';
}