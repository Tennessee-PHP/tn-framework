<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Subscriptions\BraintreePaymentMethodsEntry;

class BraintreePaymentMethodsReport extends TypeReport
{
    /** @var string */
    public string $analyticsEntryClassName = BraintreePaymentMethodsEntry::class;

    /** @var string */
    public string $reportKey = 'braintreePaymentMethods';

    /** @var string */
    public string $chartType = 'bar';
}
