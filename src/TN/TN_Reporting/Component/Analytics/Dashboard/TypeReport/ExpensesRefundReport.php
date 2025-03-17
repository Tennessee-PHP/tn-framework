<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Expenses\ExpensesRefundsEntry;

class ExpensesRefundReport extends TypeReport
{
    /** @var string  */
    public string $analyticsEntryClassName = ExpensesRefundsEntry::class;

    /** @var string  */
    public string $reportKey = 'expensesRefunds';

    /** @var string  */
    public string $chartType = 'bar';
}