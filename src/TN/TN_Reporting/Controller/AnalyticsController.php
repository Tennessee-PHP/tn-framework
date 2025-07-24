<?php

namespace TN\TN_Reporting\Controller;

use TN\TN_Core\Attribute\Command\CommandName;
use TN\TN_Core\Attribute\Command\Schedule;
use TN\TN_Core\Attribute\Command\TimeLimit;
use TN\TN_Core\Controller\Controller;
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

        // Recalculate RevenueDailyEntry from July 7th, 2025
        $startDate = strtotime('2025-07-07');
        $endDate = time(); // Current time
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            RevenueDailyEntry::updateDayReports($currentDate);
            $currentDate = strtotime('+1 day', $currentDate);
        }


        return null;
    }

    #[Path('staff/reporting/dashboard')]
    #[Component(\TN\TN_Reporting\Component\Analytics\Dashboard\Dashboard::class)]
    #[RoleOnly('sales-reporting')]
    public function dashboard(): void {}
}
