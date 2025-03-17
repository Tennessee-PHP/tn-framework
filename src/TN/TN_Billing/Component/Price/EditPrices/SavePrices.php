<?php

namespace TN\TN_Billing\Component\Price\EditPrices;

use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;

class SavePrices extends JSON
{
    public function prepare(): void
    {
        $changeCounter = 0;
        foreach (Plan::getInstances() as $plan) {
            foreach (Plan::getInstanceByKey($plan->key)->getAllPrices() as $price) {
                if ($plan->paid) {
                    // If prices have changed, call the method to update the prices
                    if (((float)$_POST[$price->planKey . '-' . $price->billingCycleKey]) !== $price->price) {
                        $changeCounter += 1;
                        $billingCycle = BillingCycle::getInstanceByKey($price->billingCycleKey);
                        Plan::getInstanceByKey($price->planKey)->setPrice($billingCycle, $_POST[$price->planKey . '-' . $price->billingCycleKey]);
                    }
                }
            }
        }

        // Get a response from this message, send it back to JS over JSON
        if ($changeCounter === 0) {
            throw new ValidationException('No changes have been made');
        }
        $this->data = [
            'success' => true,
            'message' => 'Prices have been updated'
        ];
    }
}