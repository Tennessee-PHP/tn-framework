<?php

namespace TN\TN_Reporting\Component\Refund\ListRefunds;

use TN\TN_Billing\Model\Refund\Refund;
use TN\TN_Core\Attribute\Components\FromQuery;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonArgument;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;

#[Page('List Refunds', '', false)]
#[Route('TN_Reporting:Refund:listRefunds')]
#[Reloadable]
class ListRefunds extends HTMLComponent
{
    public Pagination $pagination;
    public array $refunds;
    public $refundTotal;
    public int $refundCount;
    public mixed $yearOptions;
    public array $reasonOptions;
    #[FromQuery] public ?int $year = null;
    #[FromQuery] public ?string $reason = null;

    public function prepare(): void
    {
        $conditions = [];

        if (!empty($this->year)) {
            $conditions[] = new SearchComparison('`ts`', '>=', strtotime($this->year . '-01-01'));
            $conditions[] = new SearchComparison('`ts`', '<', strtotime(($this->year + 1) . '-01-01'));
        }

        if (!empty($this->reason)) {
            $conditions[] = new SearchComparison('`reason`', '=', $this->reason);
        }

        $search = new SearchArguments(
            conditions: $conditions,
            sorters: new SearchSorter('ts', 'DESC')
        );
        $count = Refund::count($search);
        $this->pagination = new Pagination([
            'itemCount' => $count,
            'itemsPerPage' => 200,
            'search' => $search
        ]);
        $this->pagination->prepare();
        $this->refunds = Refund::search($search);

        $totalSearch = new SearchArguments(conditions: $search->conditions);
        $this->refundTotal = 0;
        $this->refundCount = 0;
        foreach (Refund::search($totalSearch) as $refund) {
            $this->refundTotal += $refund->amount;
            $this->refundCount += 1;
        }

        $this->yearOptions = [];
        for ($year = 2022; $year <= date('Y'); $year += 1) {
            $this->yearOptions[$year] = $year;
        }

        $this->reasonOptions = Stack::resolveClassName(Refund::class)::getReasonOptions();

    }
}