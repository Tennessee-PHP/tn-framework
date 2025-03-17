<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\DashboardSummary;

use TN\TN_Reporting\Model\Analytics\Expenses\ExpensesRefundsEntry;

class ExpensesRefundsSummary extends TypeSummary
{
    public string $analyticsEntryClass = ExpensesRefundsEntry::class;

    public string $chartType = 'bar';

    public string $reportKey = 'expensesRefunds';

    public ?string $title = 'Refunds';

    public string $compareMethod = 'last';

    public string $compareKey = 'refundTotal';

    public array $dialDisplayOptions = [
        'prefix' => '$',
        'decimals' => 2
    ];
}