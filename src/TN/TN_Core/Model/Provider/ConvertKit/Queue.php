<?php

namespace TN\TN_Core\Model\Provider\ConvertKit;

use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\Storage\Cache;

/**
 * queue up a convertkit request
 *
 */
class Queue
{
    public const int USERS_FORM_ID = 2893687;

    private static $forms = [
        'schedulemaker' => 2521008,
        'users' => self::USERS_FORM_ID,
        'ratemyteam' => 3552612,
        'ratemyteam-roadblock' => 8408341,
        'showdown-optimizer' => 8853662
    ];
    private static $sequences = [
        'signup' => 1031995,
        'subscribed' => 1031997
    ];
    private static $tags = [
        'onboarding' => 2591556
    ];

    /**
     * @throws ValidationException
     */
    protected static function queueRequest(string $action, array $arguments)
    {
        // Check for duplicate requests for form subscriptions and tag additions
        if ($action === 'form_subscribe' && isset($arguments[0], $arguments[1]['email'])) {
            $formId = (string)$arguments[0];
            $email = $arguments[1]['email'];
            $setKey = ':set:ck-form-' . $email;

            $alreadyExists = Cache::setMembersContains($setKey, $formId);
            Cache::setAdd($setKey, $formId, Time::ONE_MONTH);

            if ($alreadyExists) {
                return;
            }
        } elseif ($action === 'add_tag' && isset($arguments[0], $arguments[1]['email'])) {
            $tagId = (string)$arguments[0];
            $email = $arguments[1]['email'];
            $setKey = ':set:ck-tag-' . $email;

            $alreadyExists = Cache::setMembersContains($setKey, $tagId);
            Cache::setAdd($setKey, $tagId, Time::ONE_MONTH);

            if ($alreadyExists) {
                return;
            }
        }

        $request = Request::getInstance();
        $request->update([
            'action' => $action,
            'serializedArguments' => serialize($arguments),
            'originTs' => Time::getNow()
        ]);
    }

    /**
     * subscribe an email to a form
     * @param string $email
     * @param string $formStr
     * @throws ValidationException
     */
    public static function subscribeToForm(string $email, string $formStr): void
    {
        self::queueRequest('form_subscribe', [self::$forms[$formStr], ['email' => $email]]);
    }

    /**
     * is the email address specified subscribed?
     * @param string $email
     * @return bool
     */
    public static function isSubscriber(string $email): bool
    {
        $api = new \ConvertKit_API\ConvertKit_API($_ENV['CONVERTKIT_KEY'], $_ENV['CONVERTKIT_SECRET']);
        return $api->get_subscriber_id($email) !== false;
    }

    public static function getSubscriberId(string $email): int|false
    {
        $api = new \ConvertKit_API\ConvertKit_API($_ENV['CONVERTKIT_KEY'], $_ENV['CONVERTKIT_SECRET']);
        return $api->get_subscriber_id($email);
    }

    /**
     * Queue custom-field updates for a subscriber (processed by convertkit/send-from-queue).
     *
     * @param array<string, string> $fields
     * @throws ValidationException
     */
    public static function queueSubscriberFieldUpdate(string $email, array $fields): void
    {
        self::queueRequest('update_subscriber_fields', [$email, $fields]);
    }

    /**
     * @deprecated Use queueSubscriberFieldUpdate(); kept for callers that already have a subscriber id.
     * @param array<string, string> $fields
     * @throws ValidationException
     */
    public static function updateSubscriber(int $subscriberId, array $fields): void
    {
        self::queueRequest('update_subscriber_by_id', [$subscriberId, $fields]);
    }

    /**
     * remove subscriber from all forms
     * @param string $email
     * @throws ValidationException
     */
    public static function removeSubscriber(string $email): void
    {
        self::queueRequest('form_unsubscribe', [['email' => $email]]);
    }

    /**
     * subscribe an email address to a sequence
     * @param string $email
     * @param string $sequenceStr
     * @throws ValidationException
     */
    public static function subscribeToSequence(string $email, string $sequenceStr): void
    {
        self::queueRequest('add_subscriber_to_sequence', [self::$sequences[$sequenceStr], ['email' => $email]]);
    }

    /**
     * @throws ValidationException
     */
    public static function addTag(string $email, string $tagStr): void
    {
        if (ctype_digit($tagStr)) {
            self::addTagFromId($email, (int) $tagStr);
            return;
        }

        if (isset(self::$tags[$tagStr])) {
            self::addTagFromId($email, self::$tags[$tagStr]);
            return;
        }

        $tagId = KitV3Catalog::resolveTagIdByName($tagStr);
        if ($tagId !== null) {
            self::addTagFromId($email, $tagId);
            return;
        }

        trigger_error('ConvertKit tag not found: ' . $tagStr, E_USER_ERROR);
    }

    /**
     * @throws ValidationException
     */
    public static function addTagFromId(string $email, int $tagId): void
    {
        self::queueRequest('add_tag', [$tagId, ['email' => $email]]);
    }

    /**
     * @throws ValidationException
     */
    public static function addPurchaseTag(string $email): void
    {
        $tagId = self::getPurchaseTagId();
        if ($tagId !== null) {
            self::addTagFromId($email, $tagId);
        }
    }

    public static function getPurchaseTagId(): ?int
    {
        $raw = $_ENV['CONVERTKIT_PURCHASE_TAG_ID'] ?? '';
        if ($raw === '' || !ctype_digit((string) $raw)) {
            return null;
        }
        return (int) $raw;
    }

    /**
     * @throws ValidationException
     */
    public static function addPurchase(array $options): void
    {
        self::queueRequest('create_purchase', [$options]);
    }
}
