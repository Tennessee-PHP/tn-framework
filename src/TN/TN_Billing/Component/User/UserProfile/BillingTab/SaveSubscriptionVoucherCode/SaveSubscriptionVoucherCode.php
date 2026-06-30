<?php

namespace TN\TN_Billing\Component\User\UserProfile\BillingTab\SaveSubscriptionVoucherCode;

use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\VoucherCode;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\User\User;

#[Route('TN_Billing:UserProfile:saveSubscriptionVoucherCode')]
class SaveSubscriptionVoucherCode extends JSON
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

        $voucherCodeId = (int)($_POST['voucherCodeId'] ?? 0);
        $voucherLabel = 'None';

        if ($voucherCodeId > 0) {
            $voucherCode = VoucherCode::readFromId($voucherCodeId);
            if (!$voucherCode instanceof VoucherCode) {
                throw new ValidationException('Invalid voucher code');
            }
            if (!$voucherCode->appliesToPlanKey($subscription->planKey)) {
                throw new ValidationException('This promo code cannot be applied to the user\'s current plan');
            }
            $voucherLabel = $voucherCode->code;
        }

        $subscription->update([
            'voucherCodeId' => $voucherCodeId,
            'nextTransactionAmount' => 0.00,
        ]);

        $user->subscriptionsChanged();

        $this->data = [
            'result' => 'success',
            'message' => 'Subscription voucher updated to ' . $voucherLabel . '.',
        ];
    }
}
