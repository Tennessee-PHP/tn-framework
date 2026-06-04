<?php

namespace TN\TN_Billing\Component\Subscription;

use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Service\PlanChangeService;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\User;

class ScheduleDowngrade extends JSON
{
    public function prepare(): void
    {
        $user = User::readFromId((int)($_REQUEST['id'] ?? 0));
        $subscription = Subscription::getUserActiveSubscription($user);
        if (!$subscription instanceof Subscription) {
            throw new ValidationException('No active subscription found');
        }

        if (
            !empty($_REQUEST['billingCycleKey'])
            && $_REQUEST['billingCycleKey'] !== $subscription->billingCycleKey
        ) {
            throw new ValidationException('Billing cycle cannot be changed when scheduling a downgrade');
        }

        $toPlanKey = (string)($_REQUEST['toPlanKey'] ?? '');
        $toPlan = Plan::getInstanceByKey($toPlanKey);
        if (!$toPlan instanceof Plan) {
            throw new ValidationException('Invalid target plan');
        }

        PlanChangeService::scheduleDowngrade($subscription, $toPlan);

        $this->data = [
            'result' => 'success',
            'message' => 'Your plan will change to ' . $toPlan->name . ' on your next renewal date.',
            'toPlanName' => $toPlan->name,
            'effectiveTs' => date('F j, Y', $subscription->nextTransactionTs),
        ];
    }
}
