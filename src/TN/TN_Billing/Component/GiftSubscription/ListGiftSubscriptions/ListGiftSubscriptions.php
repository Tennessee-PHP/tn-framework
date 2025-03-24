<?php

namespace TN\TN_Billing\Component\GiftSubscription\ListGiftSubscriptions;

use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\GiftSubscription;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use \TN\TN_Core\Attribute\Components\Route;

#[Page('Complimentary Subscriptions', '', false)]
#[Route('TN_Billing:GiftSubscription:listGiftSubscriptions')]
#[Reloadable]
class ListGiftSubscriptions extends HTMLComponent
{
    public Pagination $pagination;
    public array $giftSubscriptions;
    public array $billingCycles;
    public array $plans;
    public $compReasons;

    public function prepare(): void
    {
        $search = new SearchArguments(sorters: new SearchSorter('createdTs', 'DESC'));
        $count = GiftSubscription::count($search);
        $this->pagination = new Pagination([
            'itemCount' => $count,
            'itemsPerPage' => 200,
            'search' => $search
        ]);
        $this->pagination->prepare();
        $this->giftSubscriptions = GiftSubscription::search($search);
        $this->billingCycles = BillingCycle::getEnabledInstances();
        $this->plans = Plan::getInstances();
        $this->compReasons = Stack::resolveClassName(GiftSubscription::class)::getReasonOptions();
    }
}
