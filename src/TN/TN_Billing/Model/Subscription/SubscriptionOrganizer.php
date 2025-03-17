<?php

namespace TN\TN_Billing\Model\Subscription;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;

/**
 * re-organizes a user's multiple subscriptions to maximize their worth to the user
 */
class SubscriptionOrganizer
{
    protected User $user;
    protected array $before = [];
    protected array $after = [];


    /**
     * constructor
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function organize(): void
    {
        // get all current and future subscriptions
        $this->before = Subscription::getUsersActiveAndFutureSubscriptions($this->user);

        // split by mutable/immutable, then level, then finally start date
        $this->sortBefore();

        // now merge back in (only put non-rotopass after rotopass if it's the same or lower level)
        // everytime we add a sub after another, let's make sure the preceding sub is ended (equivalentGift or ?)
        $this->sortIntoAfter();
    }

    protected function sortBefore(): void
    {
        $mutables = [];
        $levels = [];
        $starts = [];
        foreach ($this->before as $subscription) {
            $gateway = $subscription->getGateway();
            $mutables[] = $gateway instanceof Gateway && $gateway->mutableSubscriptions ? 1 : 0;
            $plan = $subscription->getPlan();
            $levels[] = $plan instanceof Plan ? $plan->level : 0;
            $starts[] = $subscription->startTs;
        }
        array_multisort($mutables, SORT_ASC, $levels, SORT_DESC, $starts, SORT_ASC, $this->before);
    }

    protected function sortIntoAfter(): void
    {
        foreach ($this->before as $subscription) {
            $this->addIntoAfter($subscription);
        }
    }

    protected function addIntoAfter(Subscription $subscription): void
    {
        // if it's not mutable, DON'T touch it! just put it in and get out of here
        $gateway = $subscription->getGateway();
        if (!($gateway instanceof Gateway) || !$gateway->mutableSubscriptions) {
            $this->after[] = $subscription;
            return;
        }

        $precedingSubscription = $this->getPrecedingSubscription($subscription);
        if ($precedingSubscription instanceof Subscription) {
            if (!$precedingSubscription->hasEndTs()) {
                $precedingSubscription->endDueToReorganization();
            }

            // move the subscription to align with the end of the preceeding one
            $subscription->migrateStartTsIntoFuture($precedingSubscription->endTs);
        }
        $this->after[] = $subscription;
    }

    /**
     * @param $subscription
     * @return Subscription|null
     */
    protected function getPrecedingSubscription($subscription): ?Subscription
    {
        $level = $subscription->getPlan()->level;

        // find the latest subscription that this one can be put after without inconveniencing the user
        $latestAccessTs = Time::getNow();
        $latestAccessSubscription = null;

        foreach ($this->after as $sub) {
            if ($sub->startTs > $latestAccessTs) {
                continue;
            }
            $subLevel = $sub->getPlan()->level;
            if ($subLevel < $level) {
                continue;
            }
            // ok we got a match! We can put the subscription at least after this one
            $thisSubEnd = $sub->endTs > 0 ? $sub->endTs : $sub->nextTransactionTs;
            if ($thisSubEnd > $latestAccessTs) {
                $latestAccessTs = $thisSubEnd;
                $latestAccessSubscription = $sub;
            }
        }

        return $latestAccessSubscription;

    }
}