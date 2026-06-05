<?php

namespace TN\TN_Billing\Component\Subscription;

use TN\TN_Billing\Model\Subscription\CancellationAttempt;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Service\CancellationRecoveryService;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\User;

class AcceptRetentionOffer extends JSON
{
    public function prepare(): void
    {
        $user = User::readFromId((int)($_REQUEST['id'] ?? 0));
        $subscription = Subscription::getUserActiveSubscription($user);
        if (!$subscription instanceof Subscription) {
            throw new ValidationException('No active subscription found');
        }

        $attemptId = (int)($_REQUEST['attemptId'] ?? 0);
        $attempt = CancellationAttempt::readFromId($attemptId);
        if (!$attempt instanceof CancellationAttempt) {
            throw new ValidationException('Invalid cancellation attempt');
        }

        $result = CancellationRecoveryService::acceptRetentionOffer($attempt, $subscription, $user);

        $this->data = array_merge(['result' => 'success'], $result);
    }
}
