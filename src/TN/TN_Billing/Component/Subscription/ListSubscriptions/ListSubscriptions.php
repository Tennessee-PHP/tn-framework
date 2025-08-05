<?php

namespace TN\TN_Billing\Component\Subscription\ListSubscriptions;

use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Component\Input\Select\BillingCycleSelect\BillingCycleSelect;
use TN\TN_Core\Component\Input\Select\PlanSelect\PlanSelect;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchLogical;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorterDirection;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;

#[Page('List Subscriptions', '', false)]
#[Route('TN_Billing:Subscription:listSubscriptions')]
#[Reloadable]
class ListSubscriptions extends HTMLComponent
{
    #[FromQuery] public ?string $planKey = null;
    #[FromQuery] public ?string $billingCycleKey = null;

    public PlanSelect $planSelect;
    public BillingCycleSelect $billingCycleSelect;
    public Pagination $pagination;
    public array $subscriptions;

    public function prepare(): void
    {
        $this->planSelect = new PlanSelect(false);
        $this->planSelect->requestKey = 'planKey';
        $this->planSelect->prepare();

        $this->billingCycleSelect = new BillingCycleSelect();
        $this->billingCycleSelect->requestKey = 'billingCycleKey';
        $this->billingCycleSelect->prepare();

        // Build search conditions
        $conditions = [];

        // Only show activated subscriptions (active = 1)
        $conditions[] = new SearchComparison('`active`', '=', 1);

        // Only show currently active subscriptions (endTs = 0 or in the future)
        $currentTime = Time::getNow();
        $conditions[] = new SearchLogical('OR', [
            new SearchComparison('`endTs`', '=', 0),
            new SearchComparison('`endTs`', '>', $currentTime)
        ]);

        if (!empty($this->planKey)) {
            $conditions[] = new SearchComparison('`planKey`', '=', $this->planKey);
        }

        if (!empty($this->billingCycleKey)) {
            $conditions[] = new SearchComparison('`billingCycleKey`', '=', $this->billingCycleKey);
        }

        $search = new SearchArguments(
            conditions: $conditions,
            sorters: new SearchSorter('id', SearchSorterDirection::DESC)
        );

        $count = Subscription::count($search);
        $this->pagination = new Pagination([
            'itemCount' => $count,
            'itemsPerPage' => 50,
            'search' => $search
        ]);
        $this->pagination->prepare();

        // Get subscriptions with user data using a custom query to avoid N+1 problem
        $this->subscriptions = $this->getSubscriptionsWithUsers($search);
    }

    private function getSubscriptionsWithUsers(SearchArguments $search): array
    {
        // Use the standard search method instead of custom query for now
        // This follows TN framework patterns better
        $subscriptions = Subscription::search($search);

        // Add user data to each subscription
        foreach ($subscriptions as $subscription) {
            $subscription->user = User::readFromId($subscription->userId);
        }

        return $subscriptions;
    }
}
