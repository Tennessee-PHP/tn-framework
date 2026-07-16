<?php

namespace TN\TN_Billing\Component\User\UserProfile\BillingTab\SaveSubscriptionNextRenewalComplimentary;

use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\User\User;

#[Route('TN_Billing:UserProfile:saveSubscriptionNextRenewalComplimentary')]
class SaveSubscriptionNextRenewalComplimentary extends JSON
{
    public int $userId;

    public function prepare(): void
    {
        if ((string)$this->userId === 'me') {
            $this->userId = User::getActive()->id;
        }

        $observer = User::getActive();
        if (!$observer->hasRole('super-user') && !$observer->hasRole('user-admin')) {
            throw new ValidationException('Access denied');
        }

        $user = User::searchOne(new SearchArguments(conditions: new SearchComparison('`id`', '=', $this->userId)));
        if (!$user instanceof User) {
            throw new ValidationException('User not found');
        }

        $subscription = Subscription::getUserActiveSubscription($user);
        if (!$subscription instanceof Subscription) {
            throw new ValidationException('No active subscription found');
        }

        if ($subscription->gatewayKey !== 'braintree') {
            throw new ValidationException('Free next renewal is only available for Braintree subscriptions');
        }

        if ($subscription->hasEndTs()) {
            throw new ValidationException('Cannot set free next renewal on a subscription that has an end date');
        }

        $nextRenewalComplimentary = !empty($_POST['nextRenewalComplimentary'])
            && (string)$_POST['nextRenewalComplimentary'] !== '0';

        $subscription->update([
            'nextRenewalComplimentary' => $nextRenewalComplimentary,
        ]);

        $user->subscriptionsChanged();

        $this->data = [
            'result' => 'success',
            'message' => $nextRenewalComplimentary
                ? 'Next renewal is now free.'
                : 'Free next renewal removed. Normal pricing will apply.',
        ];
    }
}
