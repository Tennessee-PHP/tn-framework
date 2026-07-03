<?php

namespace TN\TN_Billing\Service;

use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\PlanChange;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\Transaction\Transaction;
use TN\TN_Billing\Model\VoucherCode;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Email\Email;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\Time\Time;

/**
 * Lifecycle helpers for {@see PlanChange} rows (upgrade applied immediately; downgrade schedule/apply/cancel).
 */
class PlanChangeService
{
    /**
     * @throws ValidationException
     */
    public static function recordUpgrade(
        Subscription $subscription,
        Plan $fromPlan,
        Plan $toPlan,
        ?Transaction $transaction = null
    ): PlanChange {
        $now = Time::getNow();
        $billingCycleKey = $subscription->billingCycleKey;

        $planChange = PlanChange::getInstance();
        $planChange->update([
            'userId' => $subscription->userId,
            'subscriptionId' => $subscription->id,
            'changeType' => PlanChange::CHANGE_TYPE_UPGRADE,
            'status' => PlanChange::STATUS_APPLIED,
            'fromPlanKey' => $fromPlan->key,
            'toPlanKey' => $toPlan->key,
            'fromBillingCycleKey' => $billingCycleKey,
            'toBillingCycleKey' => $billingCycleKey,
            'effectiveTs' => $now,
            'recordedTs' => $now,
            'transactionId' => $transaction instanceof Transaction ? $transaction->id : 0,
            'scheduledDowngradeMarker' => null,
        ]);

        return $planChange;
    }

    /**
     * @throws ValidationException
     */
    /**
     * @throws ValidationException
     */
    public static function validateDowngradeTarget(Subscription $subscription, Plan $toPlan): void
    {
        if ($subscription->gatewayKey !== 'braintree') {
            throw new ValidationException(
                'Plan changes require Braintree (gateway: ' . $subscription->gatewayKey . ')'
            );
        }

        if (!$subscription->getGateway()->mutableSubscriptions) {
            throw new ValidationException(
                'Plan changes are not supported for gateway "' . $subscription->gatewayKey . '"'
            );
        }

        if (!$subscription->active) {
            throw new ValidationException('Subscription is not active (active=0)');
        }

        if ($subscription->hasEndTs()) {
            $endLabel = $subscription->endTs > Time::getNow() ? 'scheduled end' : 'ended';
            throw new ValidationException(
                'Subscription has an end date (' . $endLabel . ': '
                . date('Y-m-d H:i:s', $subscription->endTs) . ', endReason=' . ($subscription->endReason ?: 'none') . ')'
            );
        }

        if ($subscription->nextTransactionTs <= 0) {
            throw new ValidationException('Subscription has no scheduled next transaction (nextTransactionTs=0)');
        }

        $fromPlan = $subscription->getPlan();
        if (!$fromPlan instanceof Plan || !$fromPlan->paid) {
            throw new ValidationException(
                'Subscription has no paid plan to downgrade from (planKey=' . $subscription->planKey . ')'
            );
        }

        if (!$toPlan->paid) {
            throw new ValidationException('Target plan is not a paid subscription tier (' . $toPlan->key . ')');
        }

        if (!$toPlan->isPurchasable()) {
            throw new ValidationException('Target plan is not available (' . $toPlan->key . ')');
        }

        if ($toPlan->level >= $fromPlan->level) {
            throw new ValidationException(
                'Target plan must be a lower tier (from ' . $fromPlan->key . ' level ' . $fromPlan->level
                . ' to ' . $toPlan->key . ' level ' . $toPlan->level . ')'
            );
        }

        if (!$toPlan->billingCycleIsCompatible($subscription->getBillingCycle())) {
            throw new ValidationException(
                'Target plan ' . $toPlan->key . ' is not available on billing cycle "'
                . $subscription->billingCycleKey . '"'
            );
        }
    }

    public static function scheduleDowngrade(
        Subscription $subscription,
        Plan $toPlan,
        bool $sendScheduledDowngradeEmail = true
    ): PlanChange
    {
        self::validateDowngradeTarget($subscription, $toPlan);

        if (self::getScheduledDowngrade($subscription) instanceof PlanChange) {
            throw new ValidationException('A downgrade is already scheduled for this subscription');
        }

        $billingCycleKey = $subscription->billingCycleKey;
        $fromPlan = $subscription->getPlan();
        if (!$fromPlan instanceof Plan) {
            throw new ValidationException('Subscription has no current plan');
        }

        $now = Time::getNow();
        $planChange = PlanChange::getInstance();
        $planChange->update([
            'userId' => $subscription->userId,
            'subscriptionId' => $subscription->id,
            'changeType' => PlanChange::CHANGE_TYPE_DOWNGRADE,
            'status' => PlanChange::STATUS_SCHEDULED,
            'fromPlanKey' => $fromPlan->key,
            'toPlanKey' => $toPlan->key,
            'fromBillingCycleKey' => $billingCycleKey,
            'toBillingCycleKey' => $billingCycleKey,
            'effectiveTs' => $subscription->nextTransactionTs,
            'recordedTs' => $now,
            'transactionId' => 0,
            'scheduledDowngradeMarker' => PlanChange::SCHEDULED_DOWNGRADE_MARKER,
        ]);

        $renewalAmount = self::computePlanRenewalAmount($subscription, $toPlan);
        $subscription->update([
            'nextTransactionAmount' => $renewalAmount,
        ]);

        if ($sendScheduledDowngradeEmail) {
            $user = $subscription->getUser();
            if ($user) {
                Email::sendFromTemplate(
                    'subscription/subscription/scheduleddowngrade',
                    $user->email,
                    [
                        'username' => $user->username,
                        'fromPlanName' => $fromPlan->name,
                        'toPlanName' => $toPlan->name,
                        'billingCycleName' => $subscription->getBillingCycle()->name,
                        'effectiveTs' => $subscription->nextTransactionTs,
                        'renewalAmount' => $renewalAmount,
                    ]
                );
            }
        }

        return $planChange;
    }

    public static function getScheduledDowngrade(Subscription $subscription): ?PlanChange
    {
        return PlanChange::searchOne(new SearchArguments([
            new SearchComparison('`subscriptionId`', '=', $subscription->id),
            new SearchComparison('`changeType`', '=', PlanChange::CHANGE_TYPE_DOWNGRADE),
            new SearchComparison('`status`', '=', PlanChange::STATUS_SCHEDULED),
        ]));
    }

    public static function deleteScheduledDowngradeIfPresent(Subscription $subscription): void
    {
        $scheduled = self::getScheduledDowngrade($subscription);
        if (!$scheduled instanceof PlanChange) {
            return;
        }

        $scheduled->erase();
        $subscription->update([
            'nextTransactionAmount' => self::computeNextRenewalAmount($subscription),
        ]);
    }

    /**
     * Deletes the scheduled downgrade row and restores {@see Subscription::$nextTransactionAmount}.
     *
     * @throws ValidationException
     */
    public static function cancelScheduledDowngrade(Subscription $subscription): void
    {
        $scheduled = self::getScheduledDowngrade($subscription);
        if (!$scheduled instanceof PlanChange) {
            throw new ValidationException('No scheduled downgrade to cancel');
        }

        $fromPlan = $subscription->getPlan();
        if (!$fromPlan instanceof Plan || !$fromPlan->isPurchasable()) {
            throw new ValidationException('This scheduled plan change cannot be cancelled');
        }

        $scheduled->erase();

        $subscription->update([
            'nextTransactionAmount' => self::computeNextRenewalAmount($subscription),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public static function markDowngradeApplied(PlanChange $planChange, Transaction $transaction): PlanChange
    {
        if ($planChange->changeType !== PlanChange::CHANGE_TYPE_DOWNGRADE) {
            throw new ValidationException('Plan change is not a downgrade');
        }

        if ($planChange->status !== PlanChange::STATUS_SCHEDULED) {
            throw new ValidationException('Downgrade is not scheduled');
        }

        $planChange->update([
            'status' => PlanChange::STATUS_APPLIED,
            'transactionId' => $transaction->id,
            'scheduledDowngradeMarker' => null,
        ]);

        return $planChange;
    }

    public static function computePlanRenewalAmount(Subscription $subscription, Plan $plan): float
    {
        $billingCycle = $subscription->getBillingCycle();
        $priceObj = $plan->getPrice($billingCycle);
        if (!$priceObj) {
            return 0.00;
        }

        $price = $priceObj->price;
        if ($subscription->voucherCodeId > 0) {
            $voucherCode = VoucherCode::readFromId($subscription->voucherCodeId);
            if ($voucherCode instanceof VoucherCode && $voucherCode->numTransactions === 0) {
                $price = $voucherCode->applyToPrice($price);
            }
        }

        return $price;
    }

    private static function computeNextRenewalAmount(Subscription $subscription): float
    {
        $plan = $subscription->getPlan();
        if (!$plan instanceof Plan) {
            return 0.00;
        }

        return self::computePlanRenewalAmount($subscription, $plan);
    }
}
