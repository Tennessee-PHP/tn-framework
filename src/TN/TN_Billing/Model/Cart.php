<?php

namespace TN\TN_Billing\Model;

use PDO;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\GiftSubscription;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Plan\Price;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\Transaction\Braintree\Transaction;
use TN\TN_Billing\Trait\GetBillingCycle;
use TN\TN_Billing\Trait\GetPlan;
use TN\TN_Core\Attribute\Constraints\EmailAddress;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\Optional;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\Email\Email;
use TN\TN_Core\Model\IP\IP;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Trait\GetUser;
use TN\TN_Reporting\Model\Campaign\Campaign;
use TN\TN_Reporting\Model\TrackedVisitor\TrackedVisitor;

/**
 * a specific user's cart. Records what a user has in their cart (currently only subscriptions) and can handle sending
 * an email to revisit and checkout their abandoned cart.
 *
 *
 */
#[TableName('carts')]
class Cart implements Persistence
{
    use MySQL;
    use PersistentModel;
    use GetBillingCycle;
    use GetPlan;
    use GetUser;

    const int ABANDONED_AFTER = 86400;

    /** @var string this is a string because it uses getUniversalIdentifier on a user so non-logged in users can still be identified */
    public string $userId = '';

    /** @var bool is this a gift purchase? */
    public bool $gift = false;

    /** @var string email of gifter */
    #[EmailAddress]
    #[Optional]
    public string $gifterEmail = '';

    /** @var string email of recipient */
    #[EmailAddress]
    #[Optional]
    public string $recipientEmail = '';

    /** @var string */
    public string $planKey = '';

    /** @var string */
    public string $billingCycleKey = '';

    /** @var int */
    #[Impersistent]
    public int $renewalTs = 0;

    /** @var string the code of the object, not an object instance itself */
    public string $voucherCode = '';

    /** @var int the last time the user interacted with the cart */
    public int $lastTs = 0;

    /** @var int
     * id of the subscription we would be able to issue credit for (and cancel!) */
    public int $creditableSubscriptionId = 0;

    /** @var string the plan key for the subscription we can give them credit for */
    public string $creditableSubscriptionPlanKey = '';

    /** @var float the creditable amount of dollars */
    public float $creditableSubscriptionCredit = 0;

    /** @var float final price of the cart */
    public float $finalPrice = 0;

    /** @var float total discounted */
    public float $discount = 0;

    /** @var int the last time the user checked out the cart */
    public int $checkedOutTs = 0;

    /** @var bool did we email them a forgotten cart email? */
    public bool $emailedReminder = false;

    /** @return array get all the carts that need reminding */
    public static function getAbandonedNonRemindedCarts(): array
    {
        return static::search(new SearchArguments([
            new SearchComparison('`lastTs`', '<', Time::getNow() - self::ABANDONED_AFTER),
            new SearchComparison('`checkedOutTs`', '=', 0),
            new SearchComparison('`emailedReminder`', '=', 0),
            new SearchComparison('`planKey`', '!=', ''),
            new SearchComparison('`billingCycleKey`', '!=', '')
        ]));
    }

    /**
     * gets a current cart for the user
     * @param User $user
     * @return Cart
     * @throws ValidationException
     */
    public static function getActiveFromUser(User $user): Cart
    {
        $cart = static::searchOne(new SearchArguments([
            new SearchComparison('`userId`', '=', $user->getUniversalIdentifier()),
            new SearchComparison('`checkedOutTs`', '=', 0)
        ]));

        // start the properties we'll update
        $updateProperties = [
            'lastTs' => Time::getNow()
        ];

        if (!$cart) {
            // create a new one
            $cart = self::getInstance();
            $updateProperties['userId'] = $user->getUniversalIdentifier();
        }

        // do the update and return the cart
        $cart->update($updateProperties);
        $cart->updateRenewalTs();
        return $cart;
    }

    /**
     * @return bool tries to remind customer to checkout their cart
     */
    public function sendEmailReminder(): bool
    {
        if ($this->emailedReminder || $this->lastTs > Time::getNow() - self::ABANDONED_AFTER) {
            return false;
        }
        try {
            $this->update([
                'emailedReminder' => true
            ]);
        } catch (ValidationException) {
            return false;
        }

        $user = $this->getUser();
        if (!($user instanceof User)) {
            return false;
        }

        Email::sendFromTemplate(
            'cart/abandoned',
            $user->email,
            [
                'planName' => $this->getPlan()->name
            ]
        );

        return true;
    }

    /** @return Plan|null gets the plan that we can potentially credit for */
    public function getCreditableSubscriptionPlan(): ?Plan
    {
        return Plan::getInstanceByKey($this->creditableSubscriptionPlanKey);
    }

    /**
     * @return string|false
     */
    public function getCreditableSubscriptionGatewayKey(): string|false
    {
        $creditableSubscription = Subscription::readFromId($this->creditableSubscriptionId);
        if (!($creditableSubscription instanceof Subscription)) {
            return false;
        }
        return $creditableSubscription->getGateway()->key;
    }

    /**
     * @return VoucherCode|null
     */
    public function getVoucherCode(): ?VoucherCode
    {
        return VoucherCode::getActiveFromCode($this->voucherCode);
    }

    /**
     * updates whether or not this is a gift
     * @param bool $gift
     * @param string $gifterEmail
     * @param string $recipientEmail
     * @return void
     * @throws ValidationException
     */
    public function updateGift(bool $gift, string $gifterEmail = '', string $recipientEmail = ''): void
    {
        if (!$gift) {
            $gifterEmail = '';
            $recipientEmail = '';
        }

        $this->update([
            'gift' => $gift,
            'gifterEmail' => $gifterEmail,
            'recipientEmail' => $recipientEmail
        ]);


        if ($this->planKey !== '') {
            $this->updateFinalPrice();
        }
    }

    /**
     * @return void updates when the renewal of the subscription would be
     * @throws ValidationException
     */
    protected function updateRenewalTs(): void
    {
        if ($this->getBillingCycle()) {
            $this->update([
                'renewalTs' => $this->getBillingCycle()->getNextTs(Time::getNow())
            ]);
        }
    }

    /**
     * updates the plan, billing cycle or code
     * @param string|null $planKey
     * @param string|null $billingCycleKey
     * @return void
     * @throws ValidationException
     */
    public function updateSubscriptionPurchase(?string $planKey = null, ?string $billingCycleKey = null): void
    {
        if ($planKey === false || $billingCycleKey === false) {
            // zero it all out
            $this->update([
                'planKey' => '',
                'billingCycleKey' => ''
            ]);
        } else {
            $errors = [];

            // make sure they are valid before updating
            $plan = Plan::getInstanceByKey($planKey);
            if (!($plan instanceof Plan)) {
                $errors[] = [
                    'error' => 'Plan does not exist'
                ];
            }
            $billingCycle = BillingCycle::getInstanceByKey($billingCycleKey);
            if (!($billingCycle instanceof BillingCycle)) {
                $errors[] = [
                    'error' => 'Billing cycle does not exist'
                ];
            } else {
                // is the billing cycle currently enabled?
                if (!$billingCycle->enabled) {
                    $errors[] = [
                        'error' => 'Billing cycle is not available'
                    ];
                }
                // can the billing cycle be applied to this plan?
                if (!$plan->billingCycleIsCompatible($billingCycle)) {
                    $errors[] = [
                        'error' => 'Billing cycle is not available for this plan'
                    ];
                }
                // if not a gift, does the user already have a subscription at this level or greater?
                if (!$this->gift && $this->getUser()) {
                    $usersPlan = $this->getUser()->getPlan();
                    $subscription = $this->getUser()->getActiveSubscription();
                    $usersBillingCycle = $subscription ? $subscription->getBillingCycle() : false;
                    if ($usersPlan instanceof Plan) {
                        if ($usersBillingCycle && $usersBillingCycle->numMonths > $billingCycle->numMonths) {
                            $errors[] = [
                                'error' => 'You cannot move from a ' . $usersBillingCycle->name . ' plan to a ' . $billingCycle->name . ' plan. Please select a plan that lasts at least as long as your current subscription.'
                            ];
                        }
                        if ($usersPlan->level === $plan->level && !($usersBillingCycle && $usersBillingCycle->numMonths < $billingCycle->numMonths)) {
                            $errors[] = [
                                'error' => 'You already have a subscription to the ' . $plan->name . ' plan'
                            ];
                        } else if ($usersPlan->level > $plan->level) {
                            $errors[] = [
                                'error' => 'You already have a subscription to a the ' . $usersPlan->name . ', which ' .
                                    'includes all the content and features of the ' . $plan->name . ' plan'
                            ];
                        }
                    }
                }
            }

            // now let's return those errors, if they exist!
            if (count($errors)) {
                throw new ValidationException($errors);
            }

            $this->update([
                'planKey' => $plan->key,
                'billingCycleKey' => $billingCycle->key
            ]);
        }

        $this->updateRenewalTs();

        // let's see if we can add a campaign and voucher code from the tracked visitor
        $trackedVisitor = TrackedVisitor::getInstance();
        $voucherCode = $trackedVisitor->getActiveVoucherCode();
        if ($voucherCode instanceof VoucherCode) {
            $this->updateVoucherCode($voucherCode->code);
        }

        // lastly, update the final price (call sub method)
        $this->updateFinalPrice();
    }

    /**
     * sets the voucher code
     * @param string|null $code
     * @return void
     * @throws ValidationException
     */
    public function updateVoucherCode(?string $code = null): void
    {
        if ($code === null) {
            $this->update([
                'voucherCode' => ''
            ]);
            return;
        }

        // now try the voucher code - is it valid for the subscription plan (if it exists)?
        $voucherCode = VoucherCode::getActiveFromCode($code);
        if (!($voucherCode instanceof VoucherCode)) {
            throw new ValidationException('This promo code doesn\'t exist or has expired already');
        }
        if (!$voucherCode->canApplyToPlan(Plan::getInstanceByKey($this->planKey))) {
            throw new ValidationException('This promo code cannot be applied to the plan');
        }

        if ($this->voucherCode === $voucherCode->code) {
            return;
        }

        $this->update([
            'voucherCode' => $voucherCode->code
        ]);

        $this->updateFinalPrice();
    }

    /** @return Price gets current price object (without discounts or credits applied here)
     *
     *
     */
    public function getPrice(): Price
    {
        return $this->getPlan()->getPrice($this->getBillingCycle());
    }

    /**
     * @return void recalculate if an existing subscription can be applied as credit, and update the appropriate fields
     * @throws ValidationException
     */
    protected function updateCreditableSubscription(): void
    {
        // if it's a gift, get on out of here!
        if ($this->gift) {
            return;
        }

        $user = $this->getUser();
        if (!($user instanceof User)) {
            return;
        }

        $subscription = Subscription::getCreditableUserSubscription($user, $this->getPlan());
        if ($subscription instanceof Subscription) {
            $this->update([
                'creditableSubscriptionId' => $subscription->id,
                'creditableSubscriptionPlanKey' => $subscription->planKey,
                'creditableSubscriptionCredit' => $subscription->getCreditAmount()
            ]);
        } else {
            $this->update([
                'creditableSubscriptionId' => 0,
                'creditableSubscriptionPlanKey' => '',
                'creditableSubscriptionCredit' => 0
            ]);
        }
    }

    /**
     * @return void calculate and update the final price
     * @throws ValidationException
     */
    protected function updateFinalPrice(): void
    {
        // now we can look for a creditable subscription and update that too! (use method)
        $this->updateCreditableSubscription();

        // get price, less discount code?, less creditable subscription
        $price = $this->getPrice()->price;
        if (!empty($this->voucherCode)) {
            $voucherCode = VoucherCode::getActiveFromCode($this->voucherCode);
            if ($voucherCode instanceof VoucherCode) {
                $price = $voucherCode->applyToPrice($price);
            } else {
                $this->update([
                    'voucherCode' => ''
                ]);
            }
        }

        if ($this->creditableSubscriptionCredit > 0 && !$this->gift) {
            $price -= $this->creditableSubscriptionCredit;
        }

        // finish it up by making sure it's positive and rounded
        $price = max($price, 0);
        $price = round($price, 2);

        $this->update([
            'finalPrice' => $price,
            'discount' => round($this->getPrice()->price - $price, 2)
        ]);
    }

    public function getUser(): ?User
    {
        // If userId contains periods (likely an IP address), return null
        if (str_contains($this->userId, '.')) {
            return null;
        }
        return User::readFromId((int)$this->userId);
    }

    /**
     * let's do the checkout!!!
     * @param string|null $nonce - if false, rely on braintree customer's vaulted payment info
     * @param string $deviceData
     * @param float $expectedPrice
     * @return Transaction
     * @throws ValidationException
     */
    public function checkout(?string $nonce, string $deviceData, float $expectedPrice): Transaction
    {
        // if gift is true, this must check that emails are set
        if ($this->gift && (empty($this->gifterEmail) || empty($this->recipientEmail))) {
            throw new ValidationException('For a gift purchase, please enter both your email and an email address for sending the instructions to redeem the gift');
        }

        if (empty($deviceData)) {
            throw new ValidationException('No device data specified');
        }

        if (!$this->getPlan()) {
            throw new ValidationException('Please select a plan');
        }
        if (!$this->getBillingCycle()) {
            throw new ValidationException('Please select a billing frequency option');
        }

        if (!$this->gift) {
            $userPlan = $this->getUser()->getPlan();
            $currentBillingCycleMonths = 0;
            if ($this->getUser()->getActiveSubscription()) {
                $currentBillingCycleMonths = $this->getUser()->getActiveSubscription()->getBillingCycle()->numMonths;
            }
            $cartBillingCycleMonths = $this->getBillingCycle()->numMonths;
            if ($userPlan instanceof Plan && $userPlan->level >= $this->getPlan()->level && $currentBillingCycleMonths >= $cartBillingCycleMonths) {
                throw new ValidationException('You already have a subscription for this level (or higher!)');
            }
        }

        // recalculate the final price
        $this->updateFinalPrice();

        // check it exactly matches (to two decimal places) the expected price
        if (round($this->finalPrice, 2) !== round($expectedPrice, 2)) {
            throw new ValidationException(
                <<<ERROR
            Looks like the price may have changed since you got to the checkout. Please check it again before
            continuing! The price might change due to a change to either the cost of the plan, whether or not your
            promo code can be applied, or because your existing subscription is now worth a different amount of credit
            to your new subscription.
            ERROR
            );
        }

        if ($this->finalPrice <= 0) {
            throw new ValidationException(
                <<<ERROR
            You can only upgrade a current annual subscription to another annual subscription - please chose "annually"
            instead of "monthly" on the checkout page.
            ERROR
            );
        }

        // create the transaction and prepare its update
        $transaction = Transaction::getInstance();
        $voucherCode = $this->getVoucherCode();
        $user = $this->getUser();
        $update = [
            'userId' => $user instanceof User ? $user->id : 0,
            'amount' => $this->finalPrice,
            'voucherCodeId' => $voucherCode instanceof VoucherCode ? $voucherCode->id : 0,
            'discount' => $this->discount,
            'ip' => IP::getAddress()
        ];

        // setup the product
        if ($this->gift) {
            // gift? create a gift subscription
            $product = GiftSubscription::getInstance();
            $product->update([
                'active' => false,
                'gifterEmail' => $this->gifterEmail,
                'recipientEmail' => $this->recipientEmail,
                'planKey' => $this->planKey,
                'billingCycleKey' => $this->billingCycleKey,
                'duration' => 1,
                'createdTs' => Time::getNow()
            ]);
            $update['giftSubscriptionId'] = $product->id;
        } else {
            // create the subscription and link it to the transaction (transaction should have subId or giftSubId on it!)
            $product = Subscription::getInstance();
            $now = Time::getNow();
            $billingCycle = $this->getBillingCycle();
            $trackedVisitor = TrackedVisitor::getInstance();
            $campaign = $trackedVisitor->getCampaign();
            $product->update([
                'active' => false,
                'userId' => $this->userId,
                'planKey' => $this->planKey,
                'billingCycleKey' => $this->billingCycleKey,
                'gatewayKey' => 'braintree',
                'voucherCodeId' => $voucherCode instanceof VoucherCode ? $voucherCode->id : 0,
                'campaignId' => $campaign instanceof Campaign ? $campaign->id : 0,
                'startTs' => $now,
                'endTs' => 0,
                'nextTransactionTs' => $billingCycle->getNextTs($now)
            ]);
            $update['subscriptionId'] = $product->id;
        }

        // now save and try the transaction
        $transaction->update($update);
        $plan = $this->getPlan();
        $billingCycle = $this->getBillingCycle();
        $price = $this->getPrice();
        $transaction->execute(
            [
                'name' => $_ENV['SITE_NAME'] . '*' . $plan->name,
                'url' => $_ENV['BASE_URL']
            ],
            [
                [
                    'description' => $plan->description,
                    'discountAmount' => $this->discount,
                    'kind' => 'debit',
                    'name' => $plan->name . ' ' . $billingCycle->name,
                    'quantity' => 1,
                    'unitAmount' => $this->finalPrice,
                    'totalAmount' => $this->finalPrice
                ]
            ],
            $nonce,
            $deviceData
        );

        if (!$transaction->success) {
            throw new ValidationException(($transaction->errorMsg ?? 'Unknown error occurred') . '. If you\'re having trouble with payment by credit card, please try paying through your PayPal account if you have one.');
        }

        // activate the product
        $product->update([
            'active' => true
        ]);

        // associate the transaction with the subscription
        $product->addSuccessfulTransaction($transaction);

        // set the cart to checked out
        $this->update([
            'checkedOutTs' => Time::getNow()
        ]);

        // cancel the creditable subscription?
        if ($this->creditableSubscriptionId > 0) {
            $creditableSubscription = Subscription::readFromId($this->creditableSubscriptionId);
            if ($creditableSubscription instanceof Subscription) {
                $creditableSubscription->upgradedToNewSubscription();
            }
        }

        // run the subscription organizer
        if ($user instanceof User) {
            $user->subscriptionsChanged();
        }

        // associate user to the voucher code?
        $voucherCode = $this->getVoucherCode();
        $user = $this->getUser();
        if ($voucherCode instanceof VoucherCode && $user instanceof User) {
            $user->usedVoucherCode($voucherCode);
        }

        return $transaction;
    }
}
