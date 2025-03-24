<?php

namespace TN\TN_Billing\Component\GiftSubscription\GetGiftSubscriptionStatus;

use TN\TN_Billing\Model\Subscription\GiftSubscription;
use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Time\Time;

class ActionGiftSubscriptionStatus extends JSON
{
    #[FromPost] public string $key;
    public ?GiftSubscription $giftSubscription;
    public function prepare(): void
    {
        $this->giftSubscription = GiftSubscription::readFromKey($this->key);
        if (!$this->giftSubscription) {
            throw new ValidationException('Gift subscription not found');
        }

        if ($this->giftSubscription->emailLastSentToRecipientTs + 3600 > Time::getNow()) {
            throw new ValidationException('A reminder was sent recently. Please wait before sending another reminder.');
        } else if ($this->giftSubscription->claimed) {
            throw new ValidationException('This gift has already been claimed.');
        } else {
            $this->giftSubscription->sendRecipientEmail();
            $this->data = [
                'result' => 'success',
                'message' => 'Reminder sent to recipient.'
            ];
        }
    }
}
