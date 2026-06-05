<?php

namespace TN\TN_Billing\Component\Subscription\ListCancellationAttempts;

use TN\TN_Billing\Model\Subscription\CancellationAttempt;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorterDirection;
use TN\TN_Core\Model\User\User;

#[Page('Cancellation Attempts', '', false)]
#[Route('TN_Billing:Subscription:listCancellationAttempts')]
#[Reloadable]
class ListCancellationAttempts extends HTMLComponent
{
    #[FromQuery] public ?string $reasonCode = null;
    #[FromQuery] public ?string $outcome = null;
    #[FromQuery] public ?string $dateFrom = null;
    #[FromQuery] public ?string $dateTo = null;

    public Pagination $pagination;
    /** @var array<int, array{attempt: CancellationAttempt, user: User|null, planName: string}> */
    public array $rows = [];
    /** @var array<string, string> */
    public array $reasonOptions;
    /** @var array<string, string> */
    public array $outcomeOptions;

    public function prepare(): void
    {
        $this->reasonOptions = CancellationAttempt::getReasonOptions();
        $this->outcomeOptions = CancellationAttempt::getOutcomeLabels();

        $conditions = [];

        if (!empty($this->reasonCode) && array_key_exists($this->reasonCode, $this->reasonOptions)) {
            $conditions[] = new SearchComparison('`reasonCode`', '=', $this->reasonCode);
        }

        if (!empty($this->outcome) && array_key_exists($this->outcome, $this->outcomeOptions)) {
            $conditions[] = new SearchComparison('`outcome`', '=', $this->outcome);
        }

        if (!empty($this->dateFrom)) {
            $fromTs = strtotime($this->dateFrom . ' 00:00:00');
            if ($fromTs !== false) {
                $conditions[] = new SearchComparison('`createdTs`', '>=', $fromTs);
            }
        }

        if (!empty($this->dateTo)) {
            $toTs = strtotime($this->dateTo . ' 23:59:59');
            if ($toTs !== false) {
                $conditions[] = new SearchComparison('`createdTs`', '<=', $toTs);
            }
        }

        $search = new SearchArguments(
            conditions: $conditions,
            sorters: new SearchSorter('createdTs', SearchSorterDirection::DESC)
        );

        $count = CancellationAttempt::count($search);
        $this->pagination = new Pagination([
            'itemCount' => $count,
            'itemsPerPage' => 50,
            'search' => $search,
        ]);
        $this->pagination->prepare();

        $offerTypeLabels = CancellationAttempt::getOfferTypeLabels();
        $attempts = CancellationAttempt::search($search);
        foreach ($attempts as $attempt) {
            $plan = Plan::getInstanceByKey($attempt->planKeyAtAttempt);
            $this->rows[] = [
                'attempt' => $attempt,
                'user' => User::readFromId($attempt->userId),
                'planName' => $plan instanceof Plan ? $plan->name : $attempt->planKeyAtAttempt,
                'reasonLabel' => $this->reasonOptions[$attempt->reasonCode] ?? $attempt->reasonCode,
                'outcomeLabel' => $attempt->outcome !== ''
                    ? ($this->outcomeOptions[$attempt->outcome] ?? $attempt->outcome)
                    : '',
                'offerLabel' => $offerTypeLabels[$attempt->offerType] ?? $attempt->offerType,
            ];
        }
    }
}
