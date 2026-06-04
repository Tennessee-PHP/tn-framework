<?php

namespace TN\TN_Billing\Service;

use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\PlanChange;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchLogical;
use TN\TN_Core\Model\Time\Time;

/**
 * Bulk-schedules downgrades for legacy top tiers (level30 → level20, level40 → level35)
 * using {@see PlanChangeService::scheduleDowngrade()} (effective on next renewal).
 */
class PlanChangeAutoDowngradeService
{
    /** @var array<string, string> fromPlanKey => toPlanKey */
    private const DOWNGRADE_MAP = [
        'level30' => 'level20',
        'level40' => 'level35',
    ];

    /**
     * @return array{
     *     dry_run: bool,
     *     candidates: int,
     *     scheduled: int,
     *     skipped_already_scheduled: int,
     *     skipped_ineligible: int,
     *     errors: array<int, string>,
     *     scheduled_by_from_plan: array<string, int>
     * }
     */
    public static function run(bool $dryRun = false): array
    {
        $stats = [
            'dry_run' => $dryRun,
            'candidates' => 0,
            'scheduled' => 0,
            'skipped_already_scheduled' => 0,
            'skipped_ineligible' => 0,
            'errors' => [],
            'scheduled_by_from_plan' => [],
        ];

        foreach (self::DOWNGRADE_MAP as $fromPlanKey => $toPlanKey) {
            $toPlan = Plan::getInstanceByKey($toPlanKey);
            if (!$toPlan instanceof Plan) {
                throw new ValidationException('Invalid target plan: ' . $toPlanKey);
            }

            foreach (self::findActiveSubscriptionsOnPlan($fromPlanKey) as $subscription) {
                $stats['candidates']++;

                if (PlanChangeService::getScheduledDowngrade($subscription) instanceof PlanChange) {
                    $stats['skipped_already_scheduled']++;
                    echo 'Subscription ' . $subscription->id . ': downgrade already scheduled' . PHP_EOL;
                    continue;
                }

                try {
                    PlanChangeService::validateDowngradeTarget($subscription, $toPlan);
                } catch (ValidationException $e) {
                    $stats['skipped_ineligible']++;
                    echo 'Subscription ' . $subscription->id . ': ineligible — ' . $e->getMessage() . PHP_EOL;
                    continue;
                }

                if ($dryRun) {
                    $stats['scheduled']++;
                    $stats['scheduled_by_from_plan'][$fromPlanKey] = ($stats['scheduled_by_from_plan'][$fromPlanKey] ?? 0) + 1;
                    echo 'Subscription ' . $subscription->id . ': would schedule ' . $fromPlanKey . ' → ' . $toPlanKey
                        . ' (effective ' . date('Y-m-d', $subscription->nextTransactionTs) . ')' . PHP_EOL;
                    continue;
                }

                try {
                    PlanChangeService::scheduleDowngrade($subscription, $toPlan);
                    $stats['scheduled']++;
                    $stats['scheduled_by_from_plan'][$fromPlanKey] = ($stats['scheduled_by_from_plan'][$fromPlanKey] ?? 0) + 1;
                    echo 'Subscription ' . $subscription->id . ': scheduled ' . $fromPlanKey . ' → ' . $toPlanKey . PHP_EOL;
                } catch (ValidationException $e) {
                    $stats['errors'][$subscription->id] = $e->getMessage();
                    echo 'Subscription ' . $subscription->id . ': error — ' . $e->getMessage() . PHP_EOL;
                }
            }
        }

        ksort($stats['scheduled_by_from_plan']);

        return $stats;
    }

    /**
     * @return Subscription[]
     */
    protected static function findActiveSubscriptionsOnPlan(string $planKey): array
    {
        return Subscription::search(new SearchArguments([
            new SearchComparison('`active`', '=', 1),
            new SearchComparison('`planKey`', '=', $planKey),
            new SearchLogical('OR', [
                new SearchComparison('`endTs`', '=', 0),
                new SearchComparison('`endTs`', '>', Time::getNow()),
            ]),
        ]));
    }
}
