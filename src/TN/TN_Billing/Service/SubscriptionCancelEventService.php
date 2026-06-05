<?php

namespace TN\TN_Billing\Service;

use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\Subscription\SubscriptionCancelEvent;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Time\Time;

/**
 * Records append-only cancel/uncancel events for subscription analytics (#55).
 */
class SubscriptionCancelEventService
{
    /**
     * @throws ValidationException
     */
    public static function recordCancel(Subscription $subscription, ?int $eventTs = null): ?SubscriptionCancelEvent
    {
        return self::record($subscription, SubscriptionCancelEvent::EVENT_TYPE_CANCEL, $eventTs);
    }

    /**
     * @throws ValidationException
     */
    public static function recordUncancel(Subscription $subscription, ?int $eventTs = null): ?SubscriptionCancelEvent
    {
        return self::record($subscription, SubscriptionCancelEvent::EVENT_TYPE_UNCANCEL, $eventTs);
    }

    /**
     * @throws ValidationException
     */
    protected static function record(
        Subscription $subscription,
        string $eventType,
        ?int $eventTs
    ): ?SubscriptionCancelEvent {
        $eventTs = $eventTs ?? Time::getNow();

        if (SubscriptionCancelEvent::existsForSubscription($subscription->id, $eventType, $eventTs)) {
            return null;
        }

        $event = SubscriptionCancelEvent::getInstance();
        $event->update([
            'userId' => $subscription->userId,
            'subscriptionId' => $subscription->id,
            'eventType' => $eventType,
            'eventTs' => $eventTs,
            'gatewayKey' => $subscription->gatewayKey,
            'planKey' => $subscription->planKey,
            'billingCycleKey' => $subscription->billingCycleKey,
        ]);

        return $event;
    }
}
