<?php

namespace TN\TN_Core\Model\Provider\ConvertKit;

use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Time\Time;

/**
 * queue up a convertkit request
 *
 */
class Queue
{
    private static $forms = [
        'schedulemaker' => 2521008,
        'users' => 2893687,
        'ratemyteam' => 3552612
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
        return false;
        $api = new \ConvertKit_API\ConvertKit_API($_ENV['CONVERTKIT_KEY'], $_ENV['CONVERTKIT_SECRET']);
        return $api->get_subscriber_id($email) !== false;
    }

    public static function getSubscriberId(string $email): int|false
    {
        return false;
        $api = new \ConvertKit_API\ConvertKit_API($_ENV['CONVERTKIT_KEY'], $_ENV['CONVERTKIT_SECRET']);
        return $api->get_subscriber_id($email);
    }

    public static function updateSubscriber(int $subscriberId, array $fields): void
    {
        return;

        $api = new \ConvertKit_API\ConvertKit_API($_ENV['CONVERTKIT_KEY'], $_ENV['CONVERTKIT_SECRET']);
        $api->make_request('v3/subscribers/' . $subscriberId, 'POST', [
            'api_secret' => $_ENV['CONVERTKIT_SECRET'],
            'fields' => $fields
        ]);
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
        if (isset(self::$tags[$tagStr])) {
            trigger_error('ConvertKit tag not found', E_USER_ERROR);
        }

        self::addTagFromId($email, self::$tags[$tagStr]);
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
    public static function addPurchase(array $options): void
    {
        self::queueRequest('create_purchase', [$options]);
    }

}