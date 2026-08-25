<?php

namespace TN\TN_Billing\Service;

use TN\TN_Billing\Model\Cart;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\CancellationAttempt;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\PlanChange;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\VoucherCode;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;

/**
 * Braintree cancellation recovery: survey, retention offers, attempt persistence.
 */
class CancellationRecoveryService
{
    public const string RETENTION_VOUCHER_CODE = 'RETENTION10';

    /**
     * @throws ValidationException
     */
    public static function assertWizardEligible(Subscription $subscription, User $user): void
    {
        if ($subscription->userId !== $user->id) {
            throw new ValidationException('Subscription does not belong to this user');
        }

        if (!$user->hasActiveBraintreeSubscription()) {
            throw new ValidationException('Cancellation recovery is only available for Braintree subscriptions');
        }

        if ($subscription->gatewayKey !== 'braintree') {
            throw new ValidationException('Cancellation recovery is only available for Braintree subscriptions');
        }

        if ($subscription->planKey === 'insider') {
            throw new ValidationException('This plan cannot be cancelled from your profile');
        }

        if ($subscription->hasEndTs()) {
            throw new ValidationException('This subscription is already cancelled');
        }

        if (!$subscription->getGateway()->mutableSubscriptions) {
            throw new ValidationException('This subscription must be managed in your app store');
        }
    }

    /**
     * @throws ValidationException
     */
    public static function saveSurvey(
        User $user,
        Subscription $subscription,
        string $reasonCode,
        string $comment
    ): CancellationAttempt {
        self::assertWizardEligible($subscription, $user);

        if (!array_key_exists($reasonCode, CancellationAttempt::getReasonOptions())) {
            throw new ValidationException('Please select a reason');
        }

        $existing = CancellationAttempt::getOpenAttemptForSubscription($subscription->id);
        $offerType = self::resolveOfferType($subscription, $user);
        if ($existing instanceof CancellationAttempt) {
            $existing->update([
                'reasonCode' => $reasonCode,
                'comment' => trim($comment),
                'offerType' => $offerType,
            ]);
            $attempt = $existing;
        } else {
            $attempt = CancellationAttempt::getInstance();
            $attempt->update([
                'userId' => $user->id,
                'subscriptionId' => $subscription->id,
                'reasonCode' => $reasonCode,
                'comment' => trim($comment),
                'billingCycleKeyAtAttempt' => $subscription->billingCycleKey,
                'planKeyAtAttempt' => $subscription->planKey,
                'offerType' => $offerType,
                'createdTs' => Time::getNow(),
            ]);
        }

        return $attempt;
    }

    /**
     * Whether the retention voucher is configured and applicable to this subscription's plan.
     */
    public static function retentionOfferAvailable(Subscription $subscription): bool
    {
        $plan = $subscription->getPlan();

        return self::findRetentionVoucher($plan instanceof Plan ? $plan : null) instanceof VoucherCode;
    }

    public static function resolveOfferType(Subscription $subscription, User $user): string
    {
        if (CancellationAttempt::userUsedRetentionOfferWithinCooldown($user->id)) {
            return CancellationAttempt::OFFER_NONE_INELIGIBLE;
        }

        if (!self::retentionOfferAvailable($subscription)) {
            return CancellationAttempt::OFFER_NONE_INELIGIBLE;
        }

        if ($subscription->billingCycleKey === 'annually') {
            return CancellationAttempt::OFFER_RENEWAL_10PCT;
        }

        return CancellationAttempt::OFFER_SWITCH_TO_ANNUAL_10PCT;
    }

    /**
     * @return array{amount: float, label: string, skipSaveStep: bool}
     */
    public static function getOfferPreview(CancellationAttempt $attempt, Subscription $subscription): array
    {
        if ($attempt->offerType === CancellationAttempt::OFFER_NONE_INELIGIBLE) {
            return ['amount' => 0.0, 'label' => '', 'skipSaveStep' => true];
        }

        $plan = $subscription->getPlan();
        $voucher = self::findRetentionVoucher($plan instanceof Plan ? $plan : null);
        if (!$voucher instanceof VoucherCode) {
            return ['amount' => 0.0, 'label' => '', 'skipSaveStep' => true];
        }

        try {
            $preview = match ($attempt->offerType) {
                CancellationAttempt::OFFER_SWITCH_TO_ANNUAL_10PCT => self::getAnnualSwitchOfferPreview($subscription, $voucher),
                CancellationAttempt::OFFER_RENEWAL_10PCT => self::getRenewalOfferPreview($subscription, $voucher),
                default => ['amount' => 0.0, 'label' => ''],
            };
        } catch (ValidationException) {
            return ['amount' => 0.0, 'label' => '', 'skipSaveStep' => true];
        }

        if ($preview['label'] === '') {
            return ['amount' => 0.0, 'label' => '', 'skipSaveStep' => true];
        }

        return [
            'amount' => $preview['amount'],
            'label' => $preview['label'],
            'skipSaveStep' => false,
        ];
    }

    public static function findRetentionVoucher(?Plan $plan = null): ?VoucherCode
    {
        $voucher = VoucherCode::getActiveFromCode(self::RETENTION_VOUCHER_CODE);
        if (!$voucher instanceof VoucherCode) {
            return null;
        }

        if ($plan instanceof Plan && !$voucher->canApplyToPlan($plan)) {
            return null;
        }

        return $voucher;
    }

    /**
     * @return array{amount: float, label: string}
     * @throws ValidationException
     */
    private static function getAnnualSwitchOfferPreview(Subscription $subscription, VoucherCode $voucher): array
    {
        $plan = $subscription->getPlan();
        if (!$plan instanceof Plan) {
            throw new ValidationException('Invalid subscription plan');
        }

        $annualCycle = BillingCycle::getInstanceByKey('annually');
        $priceObj = $plan->getPrice($annualCycle);
        if (!$priceObj) {
            throw new ValidationException('Annual billing is not available for this plan');
        }

        $amount = $voucher->applyToPrice($priceObj->price);
        $pct = $voucher->discountPercentage;

        return [
            'amount' => $amount,
            'label' => 'Switch to annual billing and save ' . $pct . '% — pay $' . number_format($amount, 2) . ' for your first annual renewal',
        ];
    }

    /**
     * @return array{amount: float, label: string}
     * @throws ValidationException
     */
    private static function getRenewalOfferPreview(Subscription $subscription, VoucherCode $voucher): array
    {
        $baseAmount = self::computeUpcomingRenewalAmount($subscription);
        $amount = $voucher->applyToPrice($baseAmount);
        $pct = $voucher->discountPercentage;

        return [
            'amount' => $amount,
            'label' => 'Get ' . $pct . '% off your next renewal — pay $' . number_format($amount, 2) . ' instead of $' . number_format($baseAmount, 2),
        ];
    }

    public static function computeUpcomingRenewalAmount(Subscription $subscription): float
    {
        if ($subscription->nextRenewalComplimentary) {
            return 0.0;
        }

        if ($subscription->nextTransactionAmount > 0) {
            return $subscription->nextTransactionAmount;
        }

        $plan = $subscription->getPlan();
        if (!$plan instanceof Plan) {
            return 0.0;
        }

        return PlanChangeService::computePlanRenewalAmount($subscription, $plan);
    }

    /**
     * Message when the subscriber already has a discount on the upcoming renewal (e.g. prior retention offer).
     */
    public static function getExistingRenewalDiscountLabel(Subscription $subscription): string
    {
        $plan = $subscription->getPlan();
        if (!$plan instanceof Plan) {
            return '';
        }

        if ($subscription->nextRenewalComplimentary) {
            return 'Your next renewal is free.';
        }

        $standardAmount = self::getStandardRenewalAmount($subscription, $plan);
        if ($standardAmount <= 0) {
            return '';
        }

        $renewalAmount = self::computeUpcomingRenewalAmount($subscription);
        if ($renewalAmount > 0 && $renewalAmount < $standardAmount) {
            $pct = (int) round((1 - $renewalAmount / $standardAmount) * 100);
            if ($pct > 0) {
                return sprintf('You are already receiving a %d%% discount on your next renewal.', $pct);
            }
        }

        if ($subscription->voucherCodeId > 0) {
            $voucher = VoucherCode::readFromId($subscription->voucherCodeId);
            if (
                $voucher instanceof VoucherCode
                && $voucher->discountPercentage > 0
                && $voucher->appliesToPlanKey($plan->key)
            ) {
                return sprintf(
                    'You are already receiving a %d%% discount on your next renewal.',
                    $voucher->discountPercentage
                );
            }
        }

        return '';
    }

    private static function getStandardRenewalAmount(Subscription $subscription, Plan $plan): float
    {
        $targetPlan = $plan;
        $scheduledDowngrade = PlanChangeService::getScheduledDowngrade($subscription);
        if (
            $scheduledDowngrade instanceof PlanChange
            && $scheduledDowngrade->effectiveTs === $subscription->nextTransactionTs
        ) {
            $toPlan = Plan::getInstanceByKey($scheduledDowngrade->toPlanKey);
            if ($toPlan instanceof Plan) {
                $targetPlan = $toPlan;
            }
        }

        $priceObj = $targetPlan->getPrice($subscription->getBillingCycle());

        return $priceObj ? (float) $priceObj->price : 0.0;
    }

    /**
     * @return array{redirectUrl?: string, message: string}
     * @throws ValidationException
     */
    public static function acceptRetentionOffer(CancellationAttempt $attempt, Subscription $subscription, User $user): array
    {
        self::assertWizardEligible($subscription, $user);

        if ($attempt->subscriptionId !== $subscription->id || $attempt->userId !== $user->id) {
            throw new ValidationException('Invalid cancellation attempt');
        }

        if (!$attempt->isOpen()) {
            throw new ValidationException('This cancellation attempt is already complete');
        }

        if ($attempt->offerType === CancellationAttempt::OFFER_NONE_INELIGIBLE) {
            throw new ValidationException('Retention offer is not available');
        }

        $plan = $subscription->getPlan();
        if (!$plan instanceof Plan) {
            throw new ValidationException('Invalid subscription plan');
        }

        $voucher = self::findRetentionVoucher($plan);
        if (!$voucher instanceof VoucherCode) {
            throw new ValidationException(
                'This retention offer is not available right now. You can still cancel your subscription from the next step.'
            );
        }

        if ($attempt->offerType === CancellationAttempt::OFFER_SWITCH_TO_ANNUAL_10PCT) {
            $cart = Cart::getActiveFromUser($user);
            $cart->updateSubscriptionPurchase($subscription->planKey, 'annually');
            $cart->updateVoucherCode($voucher->code);

            $attempt->update([
                'offerAccepted' => true,
                'outcome' => CancellationAttempt::OUTCOME_SAVED,
                'voucherCodeId' => $voucher->id,
                'completedTs' => Time::getNow(),
            ]);

            return [
                'redirectUrl' => $_ENV['BASE_URL'] . 'checkout/plan/' . $subscription->planKey . '/annually',
                'message' => 'Complete checkout to switch to annual billing with your ' . $voucher->discountPercentage . '% discount.',
            ];
        }

        // Annual subscriber: apply voucher for next renewal only (replaces any existing voucher).
        $renewalAmount = self::computeUpcomingRenewalAmount($subscription);
        $discountedAmount = $voucher->applyToPrice($renewalAmount);

        $subscription->update([
            'voucherCodeId' => $voucher->id,
            'nextTransactionAmount' => $discountedAmount,
        ]);

        $attempt->update([
            'offerAccepted' => true,
            'outcome' => CancellationAttempt::OUTCOME_SAVED,
            'voucherCodeId' => $voucher->id,
            'completedTs' => Time::getNow(),
        ]);

        return [
            'message' => 'Your ' . $voucher->discountPercentage . '% discount has been applied to your next renewal on '
                . date('F j, Y', $subscription->nextTransactionTs)
                . ' ($' . number_format($discountedAmount, 2) . ').',
        ];
    }

    /**
     * @throws ValidationException
     */
    public static function completeCancellation(CancellationAttempt $attempt, Subscription $subscription, User $user): void
    {
        self::assertWizardEligible($subscription, $user);

        if ($attempt->subscriptionId !== $subscription->id || $attempt->userId !== $user->id) {
            throw new ValidationException('Invalid cancellation attempt');
        }

        if (empty($attempt->reasonCode)) {
            throw new ValidationException('Please complete the survey first');
        }

        if (!$attempt->isOpen()) {
            throw new ValidationException('This cancellation attempt is already complete');
        }

        $subscription->cancel();

        $attempt->update([
            'outcome' => CancellationAttempt::OUTCOME_CANCELLED,
            'completedTs' => Time::getNow(),
        ]);
    }

    public static function markAbandoned(CancellationAttempt $attempt, User $user): void
    {
        if ($attempt->userId !== $user->id || !$attempt->isOpen()) {
            return;
        }

        if (empty($attempt->reasonCode)) {
            return;
        }

        $attempt->update([
            'outcome' => CancellationAttempt::OUTCOME_ABANDONED,
            'completedTs' => Time::getNow(),
        ]);
    }

    public static function getRetentionVoucher(): VoucherCode
    {
        $voucher = self::findRetentionVoucher();
        if (!$voucher instanceof VoucherCode) {
            throw new ValidationException(
                'This retention offer is not available right now. You can still cancel your subscription from the next step.'
            );
        }

        return $voucher;
    }

    public static function getReasonAcknowledgement(string $reasonCode): string
    {
        return match ($reasonCode) {
            'too_expensive' => 'We understand cost matters.',
            'not_using_enough' => 'We hear you — getting full value matters.',
            'missing_features' => 'Thanks for letting us know what\'s missing.',
            'technical_problems' => 'Sorry you\'ve had technical trouble.',
            'season_over' => 'We get it — fantasy season timing varies.',
            'switching_competitor' => 'We\'d love another chance to earn your business.',
            default => 'Thanks for sharing your feedback.',
        };
    }
}
