<?php

namespace TN\TN_Billing\Service;

use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\Subscription\SubscriptionCancelEvent;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsCancelEventsEntry;

/**
 * Best-effort backfill of cancellation events from historical user-cancelled subscriptions.
 * Uses endTs as proxy cancel date (approximate when user cancelled early in billing period).
 */
class SubscriptionCancelEventBackfillService
{
    public const int BACKFILL_YEARS = 2;

    /**
     * @return array{
     *     candidates: int,
     *     inserted: int,
     *     skipped_duplicate: int,
     *     analytics_from_ts: int|null,
     *     analytics_to_ts: int|null
     * }
     * @throws ValidationException
     */
    public static function run(bool $recomputeAnalytics = true): array
    {
        $stats = [
            'candidates' => 0,
            'inserted' => 0,
            'skipped_duplicate' => 0,
            'analytics_from_ts' => null,
            'analytics_to_ts' => null,
        ];

        $rangeStartTs = strtotime('-' . self::BACKFILL_YEARS . ' years', Time::getTodayTs());
        $rangeStartTs = strtotime(date('Y-m-d 00:00:00', $rangeStartTs));

        $subscriptions = Subscription::search(new SearchArguments([
            new SearchComparison('`endReason`', '=', 'user-cancelled'),
            new SearchComparison('`endTs`', '>=', $rangeStartTs),
            new SearchComparison('`endTs`', '>', 0),
        ]));

        $minEventTs = null;

        foreach ($subscriptions as $subscription) {
            $stats['candidates']++;
            $eventTs = $subscription->endTs;

            if (SubscriptionCancelEvent::existsForSubscription(
                $subscription->id,
                SubscriptionCancelEvent::EVENT_TYPE_CANCEL,
                $eventTs
            )) {
                $stats['skipped_duplicate']++;
                continue;
            }

            SubscriptionCancelEventService::recordCancel($subscription, $eventTs);
            $stats['inserted']++;

            if ($minEventTs === null || $eventTs < $minEventTs) {
                $minEventTs = $eventTs;
            }
        }

        if ($recomputeAnalytics && $minEventTs !== null) {
            $stats['analytics_from_ts'] = strtotime(date('Y-m-d 00:00:00', $minEventTs));
            $stats['analytics_to_ts'] = Time::getTodayTs();

            $currentTs = $stats['analytics_from_ts'];
            while ($currentTs <= $stats['analytics_to_ts']) {
                SubscriptionsCancelEventsEntry::updateDayReports($currentTs);
                $currentTs = strtotime('+1 day', $currentTs);
            }
        }

        return $stats;
    }
}
