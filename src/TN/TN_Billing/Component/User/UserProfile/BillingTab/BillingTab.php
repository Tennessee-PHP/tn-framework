<?php

namespace TN\TN_Billing\Component\User\UserProfile\BillingTab;

use TN\TN_Billing\Model\Customer\Braintree\Customer;
use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Refund\Refund;
use TN\TN_Billing\Model\Subscription\CancellationAttempt;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Plan\Price;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\VoucherCode;
use TN\TN_Billing\Service\PlanChangeService;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
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
    /** @var array{toPlanKey: string, toPlanName: string, fromPlanKey: string, fromPlanName: string, effectiveTs: int, renewalAmount: float}|null */
    public ?array $scheduledDowngradeSummary = null;
    public bool $canCancelScheduledDowngrade = false;
    public bool $canShowCancelButton = false;
    /** @var array<string, string> */
    public array $cancellationReasonOptions = [];
    public float $switchToAnnualSavings = 0.0;
    public bool $canManageSubscriptionVoucher = false;
    public int $subscriptionVoucherCodeId = 0;
    public ?VoucherCode $subscriptionVoucherCode = null;
    /** @var VoucherCode[] */
    public array $voucherCodesActive = [];
    /** @var VoucherCode[] */
    public array $voucherCodesInactive = [];
    public bool $nextRenewalComplimentary = false;
    public bool $canManageNextRenewalComplimentary = false;

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
        if (
            is_array($subscriptionPrices)
            && $this->activeSubscription instanceof Subscription
            && $this->activeSubscription->billingCycleKey !== 'annually'
        ) {
            $this->switchToAnnualSavings = round(
                ($subscriptionPrices['monthly'] * 12) - $subscriptionPrices['annually'],
                2
            );
        }
        $this->endReasonDescriptions = Subscription::getEndReasonOptions();
        if ($this->user->hasActiveBraintreeSubscription() && $subscription->hasOverduePayment()) {
            $this->braintreeOverduePayment = $subscription->nextRenewalComplimentary
                ? 0.0
                : $subscription->nextTransactionAmount;
        } else {
            $this->braintreeOverduePayment = false;
        }
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
            && in_array($this->activeSubscription->endReason, ['user-cancelled', 'refunded'], true)
        ) {
            $this->canResumeAutoRenew = true;
            $this->resumeAutoRenewHasValidPayment = $this->braintreeCustomer && $this->braintreeCustomer->hasValidVaultedPayment();
            if ($this->activeSubscription->nextRenewalComplimentary) {
                $this->resumeAutoRenewNextChargeAmount = 0.0;
            } else {
                $this->resumeAutoRenewNextChargeAmount = $this->activeSubscription->nextTransactionAmount > 0
                    ? $this->activeSubscription->nextTransactionAmount
                    : $this->activeSubscription->getPlan()->getPrice($this->activeSubscription->getBillingCycle())->price;
            }
        }

        $this->prepareDowngradeState();
        $this->cancellationReasonOptions = CancellationAttempt::getReasonOptions();
        $this->canShowCancelButton = $this->activeSubscription instanceof Subscription
            && $this->activeSubscriptionIsBraintree
            && !$this->activeSubscription->hasEndTs()
            && $this->activeSubscription->planKey !== 'insider';

        $this->prepareSubscriptionVoucherState();
        $this->prepareNextRenewalComplimentaryState();
    }

    private function prepareSubscriptionVoucherState(): void
    {
        if (!$this->activeSubscription instanceof Subscription) {
            return;
        }

        $this->subscriptionVoucherCodeId = $this->activeSubscription->voucherCodeId;
        if ($this->subscriptionVoucherCodeId > 0) {
            $voucherCode = VoucherCode::readFromId($this->subscriptionVoucherCodeId);
            if ($voucherCode instanceof VoucherCode) {
                $this->subscriptionVoucherCode = $voucherCode;
            }
        }

        if (!$this->observer->hasRole('super-user') && !$this->observer->hasRole('user-admin')) {
            return;
        }

        $this->canManageSubscriptionVoucher = true;

        $now = Time::getNow();
        foreach (VoucherCode::search(new SearchArguments()) as $voucherCode) {
            if ($voucherCode->isCurrentlyActive($now)) {
                $this->voucherCodesActive[] = $voucherCode;
            } else {
                $this->voucherCodesInactive[] = $voucherCode;
            }
        }
    }

    private function prepareNextRenewalComplimentaryState(): void
    {
        if (!$this->activeSubscription instanceof Subscription) {
            return;
        }

        $this->nextRenewalComplimentary = $this->activeSubscription->nextRenewalComplimentary;

        if (!$this->observer->hasRole('super-user') && !$this->observer->hasRole('user-admin')) {
            return;
        }

        if (!$this->activeSubscriptionIsBraintree || $this->activeSubscription->hasEndTs()) {
            return;
        }

        $this->canManageNextRenewalComplimentary = true;
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
                $renewalAmount = $this->activeSubscription->nextRenewalComplimentary
                    ? 0.0
                    : PlanChangeService::computePlanRenewalAmount($this->activeSubscription, $toPlan);
                $this->scheduledDowngradeSummary = [
                    'toPlanKey' => $toPlan->key,
                    'toPlanName' => $toPlan->name,
                    'fromPlanKey' => $currentPlan->key,
                    'fromPlanName' => $currentPlan->name,
                    'effectiveTs' => $scheduled->effectiveTs,
                    'renewalAmount' => $renewalAmount,
                ];
                $this->canCancelScheduledDowngrade = $currentPlan->isPurchasable();
            }
            return;
        }

        $options = [];
        foreach (Plan::getInstances() as $plan) {
            if (
                !$plan->paid
                || !$plan->isPurchasable()
                || $plan->level >= $currentPlan->level
                || !$plan->billingCycleIsCompatible($this->activeSubscription->getBillingCycle())
            ) {
                continue;
            }
            $options[] = [
                'key' => $plan->key,
                'name' => $plan->name,
                'price' => $this->activeSubscription->nextRenewalComplimentary
                    ? 0.0
                    : PlanChangeService::computePlanRenewalAmount($this->activeSubscription, $plan),
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
