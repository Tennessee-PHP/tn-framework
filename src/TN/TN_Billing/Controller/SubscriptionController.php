<?php

namespace TN\TN_Billing\Controller;

use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Billing\Model\Subscription\Subscription as SubscriptionModel;
use TN\TN_Billing\Service\PlanChangeAutoDowngradeService;
use TN\TN_Billing\Service\PlanChangeBackfillService;
use TN\TN_Billing\Service\SubscriptionCancelEventBackfillService;
use TN\TN_Core\Attribute\Command\CommandName;
use TN\TN_Core\Attribute\Command\Schedule;
use TN\TN_Core\Attribute\Command\TimeLimit;
use TN\TN_Core\Model\Request\Command;
use TN\TN_Core\Attribute\Route\Access\Restrictions\UsersOnly;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Model\Time\Time;

class SubscriptionController extends Controller
{
    #[Path('staff/list-subscriptions')]
    #[RoleOnly('sales-reporting')]
    #[Component(\TN\TN_Billing\Component\Subscription\ListSubscriptions\ListSubscriptions::class)]
    public function listSubscriptions(): void {}

    #[Path('staff/users/user/:userId/plans/cancel-subscription')]
    #[UsersOnly]
    #[Component(\TN\TN_Billing\Component\Subscription\CancelSubscription::class)]
    public function cancelSubscription(): void {}

    #[Path('staff/users/user/:userId/plans/cancellation-attempt')]
    #[UsersOnly]
    #[Component(\TN\TN_Billing\Component\Subscription\SaveCancellationSurvey::class)]
    public function saveCancellationSurvey(): void {}

    #[Path('staff/users/user/:userId/plans/accept-retention-offer')]
    #[UsersOnly]
    #[Component(\TN\TN_Billing\Component\Subscription\AcceptRetentionOffer::class)]
    public function acceptRetentionOffer(): void {}

    #[Path('staff/users/user/:userId/plans/abandon-cancellation-attempt')]
    #[UsersOnly]
    #[Component(\TN\TN_Billing\Component\Subscription\AbandonCancellationAttempt::class)]
    public function abandonCancellationAttempt(): void {}

    #[Path('staff/subscriptions/cancellation-attempts')]
    #[RoleOnly('sales-reporting')]
    #[Component(\TN\TN_Billing\Component\Subscription\ListCancellationAttempts\ListCancellationAttempts::class)]
    public function listCancellationAttempts(): void {}

    #[Path('staff/users/user/:userId/plans/resume-subscription')]
    #[UsersOnly]
    #[Component(\TN\TN_Billing\Component\Subscription\ResumeSubscription::class)]
    public function resumeSubscription(): void {}

    #[Path('staff/users/user/:userId/plans/schedule-downgrade')]
    #[UsersOnly]
    #[Component(\TN\TN_Billing\Component\Subscription\ScheduleDowngrade::class)]
    public function scheduleDowngrade(): void {}

    #[Path('staff/users/user/:userId/plans/cancel-scheduled-downgrade')]
    #[UsersOnly]
    #[Component(\TN\TN_Billing\Component\Subscription\CancelScheduledDowngrade::class)]
    public function cancelScheduledDowngrade(): void {}

    #[TimeLimit(7200)]
    #[CommandName('billing/schedule-hof-goat-downgrades')]
    public function scheduleHofGoatDowngrades(): ?string
    {
        $dryRun = in_array('--dry-run', Command::get()->args, true);
        echo 'Scheduling HOF/GOAT tier downgrades (dry run: ' . ($dryRun ? 'yes' : 'no') . ')' . PHP_EOL;
        echo '  level30 (HOF) → level20 (ELITE)' . PHP_EOL;
        echo '  level40 (GOAT) → level35 (All-Access)' . PHP_EOL;

        $stats = PlanChangeAutoDowngradeService::run($dryRun);

        echo json_encode($stats, JSON_PRETTY_PRINT) . PHP_EOL;

        return json_encode($stats);
    }

    #[TimeLimit(7200)]
    #[CommandName('billing/backfill-subscription-cancel-events')]
    public function backfillSubscriptionCancelEvents(): ?string
    {
        $recomputeAnalytics = !in_array('--no-analytics', Command::get()->args, true);
        echo 'Starting subscription cancel event backfill (recompute analytics: '
            . ($recomputeAnalytics ? 'yes' : 'no') . ')' . PHP_EOL;

        $stats = SubscriptionCancelEventBackfillService::run($recomputeAnalytics);

        echo json_encode($stats, JSON_PRETTY_PRINT) . PHP_EOL;

        return json_encode($stats);
    }

    #[TimeLimit(7200)]
    #[CommandName('billing/backfill-plan-changes')]
    public function backfillPlanChanges(): ?string
    {
        $recomputeAnalytics = !in_array('--no-analytics', Command::get()->args, true);
        echo 'Starting plan change backfill (recompute analytics: ' . ($recomputeAnalytics ? 'yes' : 'no') . ')' . PHP_EOL;

        $stats = PlanChangeBackfillService::run($recomputeAnalytics);

        echo json_encode($stats, JSON_PRETTY_PRINT) . PHP_EOL;

        return json_encode($stats);
    }

    #[Schedule('10 2 * * *')]
    #[TimeLimit(600)]
    #[CommandName('subscription/cancel-failed-payment-subscriptions')]
    public function cancelFailedPaymentSubscriptions(): ?string
    {
        $successes = [];
        $failures = [];
        foreach (SubscriptionModel::getFailedBillingsBeyondGracePeriod() as $subscription) {
            echo 'Subscription ID: ' . $subscription->id;
            try {
                $subscription->paymentFailedAndGracePeriodExpired();
                $success = true;
                echo 'success' . PHP_EOL;
            } catch (\Exception $e) {
                echo 'error: ' . $e->getMessage() . PHP_EOL;
                $success = false;
            }
            if ($success) {
                $successes[] = $subscription->id;
            } else {
                $failures[] = $subscription->id;
            }
        }

        return json_encode([
            'successes' => $successes,
            'failures' => $failures
        ]);
    }

    #[Schedule('*/5 * * * *')]
    #[TimeLimit(Time::ONE_MINUTE)]
    #[CommandName('subscription/attempt-auto-renew-subscriptions')]
    public function attemptAutoRenewSubscriptions(): ?string
    {
        $start = Time::getNow();
        $output = [
            'successes' => [],
            'errors' => []
        ];
        foreach (SubscriptionModel::getRecurringDueSubscriptions() as $subscription) {
            echo 'Subscription ID: ' . $subscription->id;
            try {
                $res = $subscription->recurBilling();
                echo 'transaction ID = ' . $res->id . PHP_EOL;
            } catch (\Exception $e) {
                $output['errors'][] = [
                    'subscriptionId' => $subscription->id,
                    'error' => $e->getMessage()
                ];
                echo 'error: ' . $e->getMessage() . PHP_EOL;
            }

            $now = Time::getNow();
            $expired = $now - $start;
            if ($expired >= (Time::ONE_MINUTE * 4)) {
                echo 'one expired. ending!';
                break;
            }
        }
        return json_encode($output);
    }

    #[Schedule('*/20 * * * *')]
    #[TimeLimit(Time::ONE_MINUTE * 10)]
    #[CommandName('subscription/notify-upcoming-auto-renew-subscriptions')]
    public function notifyUpcomingAutoRenewSubscriptions(): ?string
    {
        $start = Time::getNow();
        $output = [
            'successes' => [],
            'errors' => []
        ];
        foreach (SubscriptionModel::getUnNotifiedUpcomingRenewals() as $subscription) {
            echo 'Subscription ID: ' . $subscription->id;
            try {
                $subscription->notifyUpcomingRenewal();
                echo 'success';
                $output['successes'][] = $subscription->id;
            } catch (\Exception $e) {
                echo 'failed: ' . $e->getMessage();
                $output['errors'][] = [
                    'subscriptionId' => $subscription->id,
                    'error' => $e->getMessage()
                ];
            }
            echo PHP_EOL;

            $now = Time::getNow();
            $expired = $now - $start;
            if ($expired >= (Time::ONE_MINUTE - 1)) {
                echo 'one expired. ending!';
                break;
            }
        }
        return json_encode($output);
    }
}
