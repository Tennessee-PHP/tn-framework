<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Expenses\ExpensesFeesEntry;

class ExpensesFeesReport extends TypeReport
{

    /** @var string  */
    public string $analyticsEntryClassName = ExpensesFeesEntry::class;

    /** @var string  */
    public string $reportKey = 'expensesFees';

    /** @var string  */
    public string $chartType = 'bar';
}