<?php

namespace TN\TN_Billing\Controller;

use TN\TN_Billing\Model\Cart as CartModel;
use TN\TN_Core\Attribute\Command\CommandName;
use TN\TN_Core\Attribute\Command\Schedule;
use TN\TN_Core\Attribute\Command\TimeLimit;
use TN\TN_Core\Controller\Controller;

class CartController extends Controller
{
    #[Schedule('05 */2 * * * *')]
    #[TimeLimit(600)]
    #[CommandName('billing/cart/send-abandoned-cart-reminders')]
    public function sendAbandonedCartReminders(): ?string
    {
        foreach(CartModel::getAbandonedNonRemindedCarts() as $cart) {
            $cart->sendEmailReminder();
        }
        return null;
    }
}