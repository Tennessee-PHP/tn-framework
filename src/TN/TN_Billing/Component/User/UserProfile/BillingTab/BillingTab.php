<?php

namespace TN\TN_Billing\Component\User\UserProfile\BillingTab;

use TN\TN_Billing\Model\Customer\Braintree\Customer;
use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Refund\Refund;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Plan\Price;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Service\PlanChangeService;
use TN\TN_Core\Component\User\UserProfile\UserProfileTab;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;

class BillingTab extends UserProfileTab
{
    public static string $tabKey = 'billing';
    public static string $tabReadable = 'Plans &amp; Payments';
    public static int $sortOrder = 4;
    public User $observer;
    public ?string $activePlan;
    public array $refundReasons;
    public array $historicalSubscriptions;
    public array $planPrices;
    public ?Subscription $activeSubscription;
    public bool $hasHighestPlan;
    public ?Customer $braintreeCustomer;
    public string|array $subscriptionPrices;
    public array $endReasonDescriptions;
    public false|float $braintreeOverduePayment;
    public bool $subscriptionsReorganized;
    public bool $activeSubscriptionIsBraintree;
    public bool $inGracePeriod;
    public bool $canResumeAutoRenew;
    public bool $resumeAutoRenewHasValidPayment;
    public float $resumeAutoRenewNextChargeAmount;
    public bool $canScheduleDowngrade = false;
    /** @var array<int, array{key: string, name: string, price: float}> */
    public array $downgradePlanOptions = [];
    /** @var array{toPlanKey: string, toPlanName: string, fromPlanName: string, effectiveTs: int, renewalAmount: float}|null */
    public ?array $scheduledDowngradeSummary = null;

    public function prepare(): void
    {
        $this->observer = User::getActive();
        $this->activePlan = $this->user->getPlan()->name;
        $subscription = $this->user->getActiveSubscription();
        $usersPlan = Plan::getActiveUserPlan($this->user);
        $userHasHighestPlan = true;
        foreach (Plan::getInstances() as $plan) {
            if ($plan->paid && $plan->level > $usersPlan->level) {
                $userHasHighestPlan = false;
            }
        }

        if (Subscription::getUserActiveSubscription($this->user)) {
            $subscriptionPrices = Subscription::getSubscriptionPrices($this->user->getActiveSubscription());
        } else {
            $subscriptionPrices = '';
        }

        $subscriptionsReorganized = false;
        if ($this->observer->hasRole('sales-admin') && isset($_GET['reorganizesubscriptions'])) {
            $this->user->subscriptionsChanged();
            $subscriptionsReorganized = true;
        }

        $this->refundReasons = Stack::resolveClassName(Refund::class)::getReasonOptions();
        $this->historicalSubscriptions = Subscription::getUserSubscriptions($this->user, true);
        $this->planPrices = Price::readAll();
        $this->activeSubscription = Subscription::getUserActiveSubscription($this->user);
        $this->hasHighestPlan = $userHasHighestPlan;
        $this->braintreeCustomer = Customer::getExistingFromUser($this->user);
        $this->subscriptionPrices = $subscriptionPrices;
        $this->endReasonDescriptions = Subscription::getEndReasonOptions();
        $this->braintreeOverduePayment = $this->user->hasActiveBraintreeSubscription() && $subscription->hasOverduePayment() ? $subscription->nextTransactionAmount : false;
        $this->subscriptionsReorganized = $subscriptionsReorganized;
        $this->activeSubscriptionIsBraintree = $this->user->hasActiveBraintreeSubscription();
        $this->inGracePeriod = $this->activeSubscription && $this->activeSubscription->inGracePeriod();
        $this->canResumeAutoRenew = false;
        $this->resumeAutoRenewHasValidPayment = false;
        $this->resumeAutoRenewNextChargeAmount = 0.0;
        if (
            $this->activeSubscription instanceof Subscription
            && $this->activeSubscriptionIsBraintree
            && $this->activeSubscription->hasEndTs()
            && $this->activeSubscription->endTs > Time::getNow()
            && $this->activeSubscription->endReason === 'user-cancelled'
        ) {
            $this->canResumeAutoRenew = true;
            $this->resumeAutoRenewHasValidPayment = $this->braintreeCustomer && $this->braintreeCustomer->hasValidVaultedPayment();
            $this->resumeAutoRenewNextChargeAmount = $this->activeSubscription->nextTransactionAmount > 0
                ? $this->activeSubscription->nextTransactionAmount
                : $this->activeSubscription->getPlan()->getPrice($this->activeSubscription->getBillingCycle())->price;
        }

        $this->prepareDowngradeState();
    }

    private function prepareDowngradeState(): void
    {
        if (
            !$this->activeSubscription instanceof Subscription
            || !$this->activeSubscriptionIsBraintree
            || $this->activeSubscription->hasEndTs()
            || $this->activeSubscription->planKey === 'insider'
            || !$this->activeSubscription->getGateway()->mutableSubscriptions
        ) {
            return;
        }

        $currentPlan = $this->activeSubscription->getPlan();
        if (!$currentPlan instanceof Plan || !$currentPlan->paid) {
            return;
        }

        $scheduled = PlanChangeService::getScheduledDowngrade($this->activeSubscription);
        if ($scheduled) {
            $toPlan = Plan::getInstanceByKey($scheduled->toPlanKey);
            if ($toPlan instanceof Plan) {
                $this->scheduledDowngradeSummary = [
                    'toPlanKey' => $toPlan->key,
                    'toPlanName' => $toPlan->name,
                    'fromPlanName' => $currentPlan->name,
                    'effectiveTs' => $scheduled->effectiveTs,
                    'renewalAmount' => PlanChangeService::computePlanRenewalAmount($this->activeSubscription, $toPlan),
                ];
            }
            return;
        }

        $options = [];
        foreach (Plan::getInstances() as $plan) {
            if (
                !$plan->paid
                || $plan->level >= $currentPlan->level
                || !$plan->billingCycleIsCompatible($this->activeSubscription->getBillingCycle())
            ) {
                continue;
            }
            $options[] = [
                'key' => $plan->key,
                'name' => $plan->name,
                'price' => PlanChangeService::computePlanRenewalAmount($this->activeSubscription, $plan),
                'level' => $plan->level,
            ];
        }

        if (count($options) === 0) {
            return;
        }

        usort($options, static fn(array $a, array $b): int => $b['level'] <=> $a['level']);
        foreach ($options as $option) {
            unset($option['level']);
            $this->downgradePlanOptions[] = $option;
        }

        $this->canScheduleDowngrade = true;
    }
}
