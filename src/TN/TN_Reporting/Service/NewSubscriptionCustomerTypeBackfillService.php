<?php

namespace TN\TN_Reporting\Service;

use TN\TN_Core\Model\Time\Time;
use TN\TN_Reporting\Model\Analytics\Campaign\CampaignDailyEntry;
use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsNewEntry;

/**
 * One-time backfill of new-subscription analytics with customer-type and revenue dimensions.
 */
class NewSubscriptionCustomerTypeBackfillService
{
    public static function run(int $years = 2): void
    {
        $startDate = strtotime("-{$years} years", Time::getTodayTs());
        $endDate = Time::getTodayTs();
        $currentTs = $startDate;

        while ($currentTs <= $endDate) {
            echo 'Backfilling new subscription customer type analytics for ' . date('Y-m-d', $currentTs) . PHP_EOL;
            SubscriptionsNewEntry::updateDayReports($currentTs);
            CampaignDailyEntry::updateDayReports($currentTs);
            $currentTs = strtotime('+1 day', $currentTs);
        }
    }
}
