<?php

namespace TN\TN_Billing\Service;

use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\PlanChange;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\Transaction\Transaction;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchLogical;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsDowngradeEntry;
use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsUpgradeEntry;

/**
 * One-time backfill of legacy {@see PlanChange} upgrade rows from subscriptions ended with {@code endReason=upgraded}.
 */
class PlanChangeBackfillService
{
    /**
     * @return array{
     *     candidates: int,
     *     inserted: int,
     *     skipped_duplicate: int,
     *     skipped_no_chain: int,
     *     skipped_same_plan: int,
     *     skipped_invalid_plan: int,
     *     inserted_by_to_plan: array<string, int>,
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
            'skipped_no_chain' => 0,
            'skipped_same_plan' => 0,
            'skipped_invalid_plan' => 0,
            'inserted_by_to_plan' => [],
            'analytics_from_ts' => null,
            'analytics_to_ts' => null,
        ];

        $upgradedEnded = Subscription::search(new SearchArguments([
            new SearchComparison('`endReason`', '=', 'upgraded'),
            new SearchComparison('`active`', '=', 1),
            new SearchComparison('`endTs`', '>', 0),
        ]));

        $minEffectiveTs = null;
        $maxEffectiveTs = null;

        foreach ($upgradedEnded as $subscriptionA) {
            $stats['candidates']++;

            $subscriptionB = self::findChainNextSubscription($subscriptionA);
            if (!$subscriptionB instanceof Subscription) {
                $stats['skipped_no_chain']++;
                continue;
            }

            $fromPlan = Plan::getInstanceByKey($subscriptionA->planKey);
            $toPlan = Plan::getInstanceByKey($subscriptionB->planKey);
            if (!$fromPlan instanceof Plan || !$toPlan instanceof Plan) {
                $stats['skipped_invalid_plan']++;
                continue;
            }

            if ($fromPlan->key === $toPlan->key || $toPlan->level <= $fromPlan->level) {
                $stats['skipped_same_plan']++;
                continue;
            }

            if (self::hasExistingUpgradeRow($subscriptionB, $fromPlan->key, $toPlan->key)) {
                $stats['skipped_duplicate']++;
                continue;
            }

            $effectiveTs = self::resolveEffectiveTs($subscriptionB);
            $transactionId = self::resolveFirstSuccessfulTransactionId($subscriptionB);

            $planChange = PlanChange::getInstance();
            $planChange->update([
                'userId' => $subscriptionB->userId,
                'subscriptionId' => $subscriptionB->id,
                'changeType' => PlanChange::CHANGE_TYPE_UPGRADE,
                'status' => PlanChange::STATUS_APPLIED,
                'fromPlanKey' => $fromPlan->key,
                'toPlanKey' => $toPlan->key,
                'fromBillingCycleKey' => $subscriptionA->billingCycleKey,
                'toBillingCycleKey' => $subscriptionB->billingCycleKey,
                'effectiveTs' => $effectiveTs,
                'recordedTs' => $effectiveTs,
                'transactionId' => $transactionId,
                'scheduledDowngradeMarker' => null,
            ]);

            $stats['inserted']++;
            $stats['inserted_by_to_plan'][$toPlan->key] = ($stats['inserted_by_to_plan'][$toPlan->key] ?? 0) + 1;

            $minEffectiveTs = $minEffectiveTs === null ? $effectiveTs : min($minEffectiveTs, $effectiveTs);
            $maxEffectiveTs = $maxEffectiveTs === null ? $effectiveTs : max($maxEffectiveTs, $effectiveTs);
        }

        if ($recomputeAnalytics && $minEffectiveTs !== null && $maxEffectiveTs !== null) {
            $stats['analytics_from_ts'] = strtotime(date('Y-m-d 00:00:00', $minEffectiveTs));
            $stats['analytics_to_ts'] = Time::getTodayTs();
            self::recomputePlanChangeAnalytics($stats['analytics_from_ts'], $stats['analytics_to_ts']);
        }

        ksort($stats['inserted_by_to_plan']);

        return $stats;
    }

    protected static function findChainNextSubscription(Subscription $subscription): ?Subscription
    {
        return Subscription::searchOne(new SearchArguments([
            new SearchComparison('`userId`', '=', $subscription->userId),
            new SearchComparison('`startTs`', '>', $subscription->endTs),
            new SearchComparison('`startTs`', '<', $subscription->endTs + Subscription::USER_CHAIN_ALLOWED_INTERVAL),
            new SearchLogical('OR', [
                new SearchComparison('`endTs`', '>', '`startTs`'),
                new SearchComparison('`endTs`', '=', 0),
            ]),
            new SearchComparison('`active`', '=', 1),
        ]));
    }

    protected static function hasExistingUpgradeRow(Subscription $subscriptionB, string $fromPlanKey, string $toPlanKey): bool
    {
        return PlanChange::searchOne(new SearchArguments([
            new SearchComparison('`subscriptionId`', '=', $subscriptionB->id),
            new SearchComparison('`changeType`', '=', PlanChange::CHANGE_TYPE_UPGRADE),
            new SearchComparison('`status`', '=', PlanChange::STATUS_APPLIED),
            new SearchComparison('`fromPlanKey`', '=', $fromPlanKey),
            new SearchComparison('`toPlanKey`', '=', $toPlanKey),
        ])) instanceof PlanChange;
    }

    protected static function resolveEffectiveTs(Subscription $subscriptionB): int
    {
        $firstTxnTs = self::resolveFirstSuccessfulTransactionTs($subscriptionB);

        return $firstTxnTs > 0 ? $firstTxnTs : $subscriptionB->startTs;
    }

    protected static function resolveFirstSuccessfulTransactionTs(Subscription $subscription): int
    {
        $earliest = 0;
        foreach (Transaction::getAllFromSubscription($subscription) as $transaction) {
            if (!$transaction->success) {
                continue;
            }
            if ($earliest === 0 || $transaction->ts < $earliest) {
                $earliest = $transaction->ts;
            }
        }

        return $earliest;
    }

    protected static function resolveFirstSuccessfulTransactionId(Subscription $subscription): int
    {
        $earliestTs = 0;
        $earliestId = 0;
        foreach (Transaction::getAllFromSubscription($subscription) as $transaction) {
            if (!$transaction->success) {
                continue;
            }
            if ($earliestTs === 0 || $transaction->ts < $earliestTs) {
                $earliestTs = $transaction->ts;
                $earliestId = $transaction->id;
            }
        }

        return $earliestId;
    }

    protected static function recomputePlanChangeAnalytics(int $startTs, int $endTs): void
    {
        $currentTs = $startTs;
        while ($currentTs <= $endTs) {
            echo 'Recalculating plan-change analytics for ' . date('Y-m-d', $currentTs) . PHP_EOL;
            SubscriptionsUpgradeEntry::updateDayReports($currentTs);
            SubscriptionsDowngradeEntry::updateDayReports($currentTs);
            $currentTs = strtotime('+1 day', $currentTs);
        }
    }
}
