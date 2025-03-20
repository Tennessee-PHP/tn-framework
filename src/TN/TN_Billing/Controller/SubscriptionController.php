<?php

namespace TN\TN_Billing\Controller;

use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Billing\Model\Subscription\Subscription as SubscriptionModel;
use TN\TN_Core\Attribute\Command\CommandName;
use TN\TN_Core\Attribute\Command\Schedule;
use TN\TN_Core\Attribute\Command\TimeLimit;
use TN\TN_Core\Attribute\Route\Access\Restrictions\UsersOnly;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Model\Time\Time;

class SubscriptionController extends Controller
{
    #[Path('staff/users/user/:userId/plans/cancel-subscription')]
    #[UsersOnly]
    #[Component(\TN\TN_Billing\Component\Subscription\CancelSubscription::class)]
    public function cancelSubscription(): void {}

    #[Schedule('10 2 * * * *')]
    #[TimeLimit(600)]
    #[CommandName('subscription/cancel-failed-payment-subscriptions')]
    public function cancelFailedPaymentSubscriptions(): ?string
    {
        $successes = [];
        $failures = [];
        foreach (SubscriptionModel::getFailedBillingsBeyondGracePeriod() as $subscription) {
            echo 'Subscription ID: ' . $subscription->id;
            $success = $subscription->paymentFailedAndGracePeriodExpired();
            echo $success === true ? 'success' : 'failed';
            echo "\n";
            if ($success) {
                $successes[] = $subscription->id;
            } else {
                $failures[] = $subscription->id;
            }
        }
        return json_encode([
            'success' => $successes,
            'failures' => $failures
        ]);
    }

    #[Schedule('*/20 * * * * *')]
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
            if ($expired >= (Time::ONE_MINUTE - 1)) {
                echo 'one expired. ending!';
                break;
            }
        }
        return json_encode($output);
    }

    #[Schedule('*/20 * * * * *')]
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
            } catch(\Exception $e) {
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