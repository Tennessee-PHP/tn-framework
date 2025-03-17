<?php

namespace TN\TN_Core\Trait\Options;

use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Core\Model\User\User;

/**
 * helps an object define its audience from one of a few different options
 * 
 */
trait Audience
{
    /**
     * get the possible audience options
     * @return string[]
     */
    public static function getAudienceOptions(): array
    {
        $options = [
            'everyone' => 'Absolutely Everyone',
            'non_users' => 'Anyone Who is Not Logged In',
            'users' => 'Anyone Who is Logged In',
            'all_unpaid' => 'Anyone Without a Paid Subscription',
            'paid_users' => 'Users - With a Paid Subscription',
            'unpaid_users' => 'Users - Without a Paid Subscription'
        ];
        foreach (Plan::getInstances() as $plan) {
            if ($plan->paid) {
                $options['plan:' . $plan->key] = $plan->name . ' Subscribers';
            }
        }
        return $options;
    }

    /**
     * @param User $user
     * @return bool
     */
    protected function userIsInAudience(User $user): bool
    {
        switch ($this->audience) {
            case 'everyone':
                return true;
            case 'non_users':
                return !$user->loggedIn;
            case 'users':
                return $user->loggedIn;
            case 'paid_users':
                return $user->isPaidSubscriber();
            case 'unpaid_users':
                return $user->loggedIn && !$user->isPaidSubscriber();
            case 'all_unpaid':
                return !$user->isPaidSubscriber();
        }

        // wasn't any of those! probably a sub:
        if (str_starts_with($this->audience, 'plan:')) {
            $userPlan = $user->getPlan();
            return $userPlan instanceof Plan && $userPlan->key === substr($this->audience, 5);
        }

        // don't know! exit false
        return false;
    }
}