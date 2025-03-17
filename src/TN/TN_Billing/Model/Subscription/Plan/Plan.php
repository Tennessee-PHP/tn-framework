<?php

namespace TN\TN_Billing\Model\Subscription\Plan;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\User;

/**
 * a plan to subscribe to
 *
 * @see \TN\TN_Billing\Model\Subscription\Subscription
 *
 */
abstract class Plan
{
    use \TN\TN_Core\Trait\Getter;
    use \TN\TN_Core\Trait\ExtendedSingletons;

    /** @var string non-db identifier */
    protected string $key;

    /** @var int the level of the plan. this plan grants access to anything below this level */
    protected int $level;

    /** @var string the name of the plan (user-facing) */
    protected string $name;

    /** @var bool whether the plan can ever be paid */
    protected bool $paid;

    /** @var string a description for the plan */
    protected string $description;

    /**
     * gets the lowest level plan with >= level
     * @param int $level
     * @return ?Plan
     */
    public static function getPlanForLevel(int $level): ?Plan
    {
        $plans = self::getInstances();
        $planLevels = [];
        foreach ($plans as $plan) {
            $planLevels[] = $plan->level;
        }
        array_multisort($planLevels, SORT_ASC, $plans);
        foreach ($plans as $plan) {
            if ($plan->level >= $level) {
                return $plan;
            }
        }
        return null;
    }

    /**
     * gets current active plan with highest level, or false
     * @param User $user
     * @return ?Plan
     */
    public static function getActiveUserPlan(User $user): ?Plan
    {
        $userPlans = [];
        $allPlans = Plan::getInstances();
        $subscription = $user->getActiveSubscription();
        if ($subscription instanceof Subscription) {
            $userPlans[] = Plan::getInstanceByKey($subscription->planKey);
        }

        // let's also see if any of the plans want to auto-grant themselves to the user
        foreach ($allPlans as $plan) {
            if ($plan->userHasWithoutSubscription($user)) {
                $userPlans[] = $plan;
            }
        }

        if (count($userPlans) === 0) {
            return null;
        }

        foreach ($userPlans as &$plan) {
            $plan = $plan->key;
        }
        $userPlans = array_unique($userPlans);
        $levels = [];
        foreach ($userPlans as $planKey) {
            $levels[] = Plan::getInstanceByKey($planKey)->level;
        }
        array_multisort($levels, SORT_DESC, $userPlans);

        // now let's return the plan of the highest level
        return Plan::getInstanceByKey($userPlans[0]);
    }

    /**
     * the user can access this plan without a subscription? usually false!
     * @param User $user
     * @return bool
     */
    public function userHasWithoutSubscription(User $user): bool
    {
        return false;
    }

    /** @return array get the billing cycles that can work with this plan */
    public function getCompatibleBillingCycles(): array
    {
        return BillingCycle::getEnabledInstances();
    }

    /**
     * is a specific billing cycle compatible with this plan?
     * @param BillingCycle $billingCycle
     * @return bool
     */
    public final function billingCycleIsCompatible(BillingCycle $billingCycle): bool
    {
        return in_array($billingCycle, $this->getCompatibleBillingCycles());
    }

    /** @return BillingCycle gets the default billing cycle for this plan */
    public function getDefaultBillingCycle(): BillingCycle
    {
        return BillingCycle::getInstanceByKey('annually');
    }

    /** @return array returns an associative array of billing cycle keys as the keys, and prices as the values */
    public function getAllPrices(): array
    {
        $prices = [];
        foreach($this->getCompatibleBillingCycles() as $billingCycle) {
            $prices[$billingCycle->key] = $this->getPrice($billingCycle);
        }
        return $prices;
    }

    /**
     * get a default price for this plan and billing cycle
     * @param BillingCycle $billingCycle
     * @return float
     */
    abstract protected function getDefaultPrice(BillingCycle $billingCycle): float;

    /**
     * get the current price for the billing cycle specified
     * @param BillingCycle $billingCycle
     * @return ?Price
     */
    public function getPrice(BillingCycle $billingCycle): ?Price
    {
        if (!$this->paid) {
            return null;
        }

        $price = Price::readFromKeys($this->key, $billingCycle->key);
        if ($price instanceof Price) {
            return $price;
        }

        $price = Price::getInstance();
        $price->update([
            'planKey' => $this->key,
            'billingCycleKey' => $billingCycle->key,
            'price' => $this->getDefaultPrice($billingCycle)
        ]);

        return $price;
    }

    /**
     * updates the current price for the billing cycle specified
     * @param BillingCycle $billingCycle
     * @param float $amount
     * @throws ValidationException
     */
    public function setPrice(BillingCycle $billingCycle, float $amount): void
    {
        if (!$this->paid) {
            return;
        }

        $price = Price::readFromKeys($this->key, $billingCycle->key);
        if ($price instanceof Price) {
            $price->update([
                'price' => $amount
            ]);
        } else {
            $price = Price::getInstance();
            $price->update([
                'planKey' => $this->key,
                'billingCycleKey' => $billingCycle->key,
                'price' => $amount
            ]);
        }
    }
}