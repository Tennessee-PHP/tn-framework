<?php

namespace TN\TN_Billing\Component\User\UserProfile\BillingTab;

use TN\TN_Billing\Model\Customer\Braintree\Customer;
use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Refund\Refund;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Plan\Price;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Component\User\UserProfile\UserProfileTab;
use TN\TN_Core\Model\Package\Stack;
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
        if ($this->observer->hasRole('user-admin') && isset($_GET['reorganizesubscriptions'])) {
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
    }
}
