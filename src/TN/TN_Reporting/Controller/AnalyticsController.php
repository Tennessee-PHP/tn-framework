<?php

namespace TN\TN_Reporting\Controller;

use TN\TN_Core\Attribute\Command\CommandName;
use TN\TN_Core\Attribute\Command\Schedule;
use TN\TN_Core\Attribute\Command\TimeLimit;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Model\Storage\Cache;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Reporting\Model\Analytics\Expenses\ExpensesFeesEntry;
use TN\TN_Reporting\Model\Analytics\Expenses\ExpensesRefundsEntry;
use TN\TN_Reporting\Model\Analytics\Revenue\RevenueDailyEntry;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;
use TN\TN_Reporting\Model\Analytics\Campaign\CampaignDailyEntry;
use TN\TN_Reporting\Model\Analytics\Revenue\RevenuePerSubscriptionEntry;
use TN\TN_Reporting\Model\Analytics\Revenue\RevenueRecurringEntry;
use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsActiveEntry;
use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsChurnEntry;
use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsEndedEntry;
use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsLifetimeValueEntry;
use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsNewEntry;
use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsRenewalEntry;
use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsStalledEntry;
use TN\TN_Reporting\Model\Analytics\Users\UsersRegistrationsEntry;

class AnalyticsController extends Controller
{
    #[Schedule('*/10 * * * *')]
    #[TimeLimit(5000)]
    #[CommandName('reporting/analytics/update')]
    public function updateAnalytics(): ?string
    {
        // One-time recalculation since July 4th, 2025 (uses Redis to ensure it only runs once)
        $redisKey = 'analytics_recalc_july_2025_done';
        if (!Cache::get($redisKey)) {
            echo "Starting one-time recalculation from July 4, 2025...\n";

            $startDate = strtotime('2025-07-04');
            $endDate = Time::getTodayTs();

            $currentTs = $startDate;
            while ($currentTs <= $endDate) {
                echo "Recalculating for " . date('Y-m-d', $currentTs) . "\n";

                // Only recalculate the three specific report types
                RevenueRecurringEntry::updateDayReports($currentTs);
                RevenuePerSubscriptionEntry::updateDayReports($currentTs);
                SubscriptionsLifetimeValueEntry::updateDayReports($currentTs);

                $currentTs = strtotime('+1 day', $currentTs);
            }

            // Mark as completed in Redis (expires in 30 days)
            Cache::set($redisKey, true, 86400 * 30);
            echo "One-time recalculation completed!\n";
        }

        // Regular daily analytics update
        $tses = [Time::getTodayTs()];

        // if it's between 00:00:00 and 01:00:00, let's do yesterday too
        if (date('H') < 1) {
            $tses[] = strtotime('-1 day', Time::getTodayTs());
        }

        foreach ($tses as $ts) {
            RevenueDailyEntry::updateDayReports($ts);
            SubscriptionsChurnEntry::updateDayReports($ts);
            SubscriptionsActiveEntry::updateDayReports($ts);
            RevenueRecurringEntry::updateDayReports($ts);
            RevenuePerSubscriptionEntry::updateDayReports($ts);
            SubscriptionsLifetimeValueEntry::updateDayReports($ts);
            UsersRegistrationsEntry::updateDayReports($ts);
            SubscriptionsNewEntry::updateDayReports($ts);
            SubscriptionsRenewalEntry::updateDayReports($ts);
            SubscriptionsStalledEntry::updateDayReports($ts);
            SubscriptionsEndedEntry::updateDayReports($ts);
            ExpensesFeesEntry::updateDayReports($ts);
            ExpensesRefundsEntry::updateDayReports($ts);
            CampaignDailyEntry::updateDayReports($ts);
        }

        return null;
    }

    #[Path('staff/reporting/dashboard')]
    #[Component(\TN\TN_Reporting\Component\Analytics\Dashboard\Dashboard::class)]
    #[RoleOnly('sales-reporting')]
    public function dashboard(): void {}
}
