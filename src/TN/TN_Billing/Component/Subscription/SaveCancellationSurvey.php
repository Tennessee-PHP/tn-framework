<?php

namespace TN\TN_Billing\Component\Subscription;

use TN\TN_Billing\Model\Subscription\CancellationAttempt;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Service\CancellationRecoveryService;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\User;

class SaveCancellationSurvey extends JSON
{
    public function prepare(): void
    {
        $user = User::readFromId((int)($_REQUEST['id'] ?? 0));
        $subscription = Subscription::getUserActiveSubscription($user);
        if (!$subscription instanceof Subscription) {
            throw new ValidationException('No active subscription found');
        }

        $reasonCode = (string)($_REQUEST['reasonCode'] ?? '');
        $comment = (string)($_REQUEST['comment'] ?? '');

        $attempt = CancellationRecoveryService::saveSurvey($user, $subscription, $reasonCode, $comment);
        $offerPreview = CancellationRecoveryService::getOfferPreview($attempt, $subscription);

        if ($offerPreview['skipSaveStep'] && $attempt->offerType !== CancellationAttempt::OFFER_NONE_INELIGIBLE) {
            $attempt->update(['offerType' => CancellationAttempt::OFFER_NONE_INELIGIBLE]);
        }

        $this->data = [
            'result' => 'success',
            'attemptId' => $attempt->id,
            'offerType' => $attempt->offerType,
            'offerAmount' => $offerPreview['amount'],
            'offerLabel' => $offerPreview['label'],
            'reasonAcknowledgement' => CancellationRecoveryService::getReasonAcknowledgement($attempt->reasonCode),
            'skipSaveStep' => $offerPreview['skipSaveStep'],
        ];
    }
}
