<?php

namespace TN\TN_Billing\Model\Subscription;

use JetBrains\PhpStorm\ArrayShape;
use PDO;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Transaction\Transaction;
use TN\TN_Billing\Trait\GetBillingCycle;
use TN\TN_Billing\Trait\GetPlan;
use TN\TN_Core\Attribute\Constraints\Inclusion;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\AutoIncrement;
use TN\TN_Core\Attribute\MySQL\PrimaryKey;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\Optional;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\Email\Email;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Strings\Strings;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Model\Validation\Validation;

/**
 * gift or complimentary subscriptions
 */
#[TableName('gift_subscriptions')]
class GiftSubscription implements Persistence
{
    use MySQL;
    use PersistentModel;
    use GetPlan;
    use GetBillingCycle;

    /** @var bool has it been paid for? */
    public bool $active = false;

    /** @var string this is a hashed, unique key to use in URLs */
    public string $key;

    /** @var int user's id */
    public int $claimedByUserId = 0;

    /** @var bool has the subscription been claimed? */
    public bool $claimed = false;

    /** @var string the gifter's email */
    public string $gifterEmail = '';

    /** @var string the recipient's email */
    public string $recipientEmail = '';

    /** @var string plan key */
    public string $planKey;

    /** @var string billing cycle key */
    public string $billingCycleKey;

    /** @var int the number of billing cycles */
    public int $duration;

    /** @var int when the gift subscription was created */
    public int $createdTs;

    /** @var string the reason for giving the complimentary subscription */
    #[Inclusion('()getReasonOptions|keys')]
    #[Optional]
    public string $complimentaryReason = '';

    /** @var int when was the email last sent to recipient? */
    public int $emailLastSentToRecipientTs = 0;

    /** @var float how much the transaction cost */
    public float $transactionAmount = 0.00;

    /** @var string gift or a complimentary sub? */
    #[Inclusion(['', 'gift', 'complimentary'])]
    public string $type = 'gift';

    #[Impersistent]
    public ?User $claimedByUserRecord = null;

    public function __get(string $prop): mixed
    {
        if ($prop === 'claimedByUser') {
            if (!$this->claimedByUserRecord && $this->claimedByUserId) {
                $this->claimedByUserRecord = User::readFromId($this->claimedByUserId);
            }
            return $this->claimedByUserRecord;
        }

        if ($prop === 'plan') {
            return $this->getPlan();
        }

        return null;
    }

    /** @return string[] get the possible reason options */
    public static function getReasonOptions(): array
    {
        return [];
    }

    /** @return array get all the complimentary subscriptions created in the last year */
    public static function getRecentComplimentarySubscriptions(): array
    {
        return static::search(new SearchArguments([
            new SearchComparison('`createdTs`', '>', Time::getNow() - (86400 * 365)),
            new SearchComparison('`type`', '=', 'complimentary')
        ], new SearchSorter('createdTs', 'DESC')));
    }

    /**
     * read a gift subscription from the key
     * @param string $key
     * @return ?GiftSubscription
     */
    public static function readFromKey(string $key): ?GiftSubscription
    {
        $results = self::searchByProperty('key', $key);
        return $results[0] ?? null;
    }

    /**
     * @param string $planKey
     * @param string $billingCycleKey
     * @param int $duration
     * @param string $reason
     * @param string $emails
     * @return array
     * @throws ValidationException
     */
    public static function createComplimentarySubscriptions(
        string $planKey,
        string $billingCycleKey,
        int $duration,
        string $reason,
        string $emails
    ): array {
        $plan = Plan::getInstanceByKey($planKey);
        if ($plan === false) {
            trigger_error('createComplimentarySubscriptions invalid plan key specified');
        }
        $billingCycle = BillingCycle::getInstanceByKey($billingCycleKey);
        if ($billingCycle === false) {
            trigger_error('createComplimentarySubscriptions invalid billing cycle key specified');
        }
        if ($duration <= 0) {
            trigger_error('createComplimentarySubscriptions duration must be > 0');
        }

        $emails = preg_split("/[\s,]+/", $emails);

        $result = [
            'invalid_emails' => [],
            'failed_emails' => [],
            'success_emails' => [],
            'created_ids' => []
        ];

        /**
         * trim and validate each email. for each fail add them to invalid_emails
         */
        foreach ($emails as $email) {
            $email = trim($email);
            if (Validation::email($email)) {
                // let's create those complimentary subscriptions!
                try {
                    $gift = GiftSubscription::getInstance();
                    $gift->update([
                        'active' => true,
                        'claimed' => false,
                        'gifterEmail' => '',
                        'recipientEmail' => $email,
                        'type' => 'complimentary',
                        'planKey' => $planKey,
                        'billingCycleKey' => $billingCycleKey,
                        'duration' => $duration,
                        'complimentaryReason' => $reason,
                        'createdTs' => Time::getNow()
                    ]);
                    $result['created_ids'][] = $gift->id;
                    $result['success_emails'][] = $email;
                } catch (ValidationException) {
                    $result['failed_emails'][] = $email;
                }
            } else {
                $result['invalid_emails'][] = $email;
            }
        }

        return $result;
    }

    /** @param Transaction $transaction associate the transaction with this subscription
     * @throws ValidationException
     */
    public function addSuccessfulTransaction(Transaction $transaction): void
    {
        $this->update([
            'transactionAmount' => $transaction->amount
        ]);
    }

    /** add the hashed key */
    protected function beforeSave(array $changedProperties): array
    {
        if (!isset($this->id)) {
            // we need to add the password hash value
            $this->key = Strings::generateRandomString();
            return ['key'];
        }
        return [];
    }

    /** just inserted - send both parties an email if active */
    protected function afterSaveInsert(): void
    {
        if ($this->active) {
            $this->sendRecipientEmail();
            $this->sendGifterEmail();
        }
    }

    /** @param array $changedProperties after a save update */
    protected function afterSaveUpdate(array $changedProperties): void
    {
        if (in_array('active', $changedProperties) && $this->active) {
            $this->sendRecipientEmail();
            $this->sendGifterEmail();
        }
    }


    /**
     * redeem it against a user for a new subscription. return that new subscription or errors
     * @param User $user
     * @return Subscription|array
     * @throws ValidationException
     */
    public function redeem(User $user): Subscription|array
    {
        // the only thing that's tricky here is figuring out the startTs.
        // so it needs to be now by default,
        // UNLESS the user has a subscription with an equal or higher level to this plan
        // in which case, the startTs is either the endTs OR the next transaction of that plan (and END it!)
        $startTs = Time::getNow();
        $billingCycle = $this->getBillingCycle();
        $plan = $this->getPlan();
        $errors = [];

        if (!$this->active) {
            $errors[] = [
                'error' => 'This gift subscription is not active'
            ];
        }

        if (!($billingCycle instanceof BillingCycle)) {
            $errors[] = [
                'error' => 'Billing cycle no longer exists'
            ];
        }
        if (!($plan instanceof Plan)) {
            $errors[] = [
                'error' => 'Plan no longer exists'
            ];
        }
        if ($this->duration <= 0) {
            $errors[] = [
                'error' => 'Duration is not a positive number'
            ];
        }
        if (count($errors) > 0) {
            return $errors;
        }

        // now keep iterating over getNextTs on the BillingCycle $this->duration times for the endTs
        $endTs = $startTs;
        for ($i = 0; $i < $this->duration; $i += 1) {
            $endTs = $billingCycle->getNextTs($endTs);
        }

        $subscription = Subscription::getInstance();
        $subscription->update([
            'userId' => $user->id,
            'planKey' => $this->planKey,
            'billingCycleKey' => $this->billingCycleKey,
            'gatewayKey' => 'free',
            'startTs' => $startTs,
            'endTs' => $endTs,
            'active' => true
        ]);

        // now save this gift subscription to redeemed - and if it failed, erase the subscription created!
        try {
            $this->update([
                'claimedByUserId' => $user->id,
                'claimed' => true
            ]);
        } catch (ValidationException $e) {
            $subscription->erase();
            throw $e;
        }

        // make sure to organize subscriptions after redemption
        $user->subscriptionsChanged();

        // finally, email both the gifter and the recipient
        if (!empty($this->gifterEmail)) {
            Email::sendFromTemplate(
                'subscription/giftsubscription/redeemed/gifter',
                $this->gifterEmail,
                [
                    'recipient' => $user->username,
                    'planName' => Plan::getInstanceByKey($this->planKey)->name
                ]
            );
        }

        Email::sendFromTemplate(
            'subscription/giftsubscription/redeemed/recipient',
            $this->recipientEmail,
            [
                'gifterEmail' => $this->gifterEmail,
                'username' => $user->username,
                'planName' => Plan::getInstanceByKey($this->planKey)->name
            ]
        );

        return $subscription;
    }

    /** @return bool send the recipient the email they need to redeem the subscription. returns false if done recently or otherwise fails */
    public function sendRecipientEmail(): bool
    {
        if ($this->emailLastSentToRecipientTs + 3600 > Time::getNow() || $this->claimed) {
            return false;
        }

        if ($this->type === 'complimentary') {
            return Email::sendFromTemplate(
                //                ($this->emailLastSentToRecipientTs > 0 ? 'REMINDER: ' : '') .
                //                'You\'re Awesome - FREE ' . $_ENV['SITE_NAME'] . ' Gift Subscription Inside!',
                'subscription/giftsubscription/recipientcomplimentary',
                $this->recipientEmail,
                [
                    'giftSubscriptionKey' => $this->key,
                    'planName' => $this->getPlan()->name,
                    'billingCycleNumMonths' => $this->getBillingCycle()->numMonths
                ]
            );
        } else {
            return Email::sendFromTemplate(
                //                ($this->emailLastSentToRecipientTs > 0 ? 'REMINDER: ' : '') .
                //                'Somebody Must Love You - FREE ' . $_ENV['SITE_NAME'] . ' Gift Subscription Inside!',
                'subscription/giftsubscription/recipient',
                $this->recipientEmail,
                [
                    'giftSubscriptionKey' => $this->key,
                    'gifterEmail' => $this->gifterEmail,
                    'planName' => $this->getPlan()->name,
                    'billingCycleNumMonths' => $this->getBillingCycle()->numMonths
                ]
            );
        }
    }

    /** @return bool send the gifter the email to let them know it was all done successfully */
    protected function sendGifterEmail(): bool
    {
        if ($this->type === 'complimentary') {
            return false;
        }
        return Email::sendFromTemplate(
            'subscription/giftsubscription/gifter',
            $this->gifterEmail,
            [
                'giftSubscriptionKey' => $this->key,
                'recipientEmail' => $this->recipientEmail,
                'planName' => $this->getPlan()->name,
                'billingCycleNumMonths' => $this->getBillingCycle()->numMonths
            ]
        );
    }
}
