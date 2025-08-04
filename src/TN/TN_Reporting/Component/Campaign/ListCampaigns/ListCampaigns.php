<?php

namespace TN\TN_Reporting\Component\Campaign\ListCampaigns;

use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorterDirection;
use TN\TN_Reporting\Model\Campaign\Campaign;
use TN\TN_Reporting\Model\Funnel\Funnel;

#[Page('List Campaigns', '', false)]
#[Route('TN_Reporting:Campaign:listCampaigns')]
#[Breadcrumb('Campaign')]
#[Reloadable]
class ListCampaigns extends HTMLComponent
{
    #[FromPost] public ?int $toggleArchiveCampaignId = null;
    public Pagination $pagination;
    public array $campaigns;
    public array $funnels;

    public function prepare(): void
    {
        // Handle archive toggle if requested
        if ($this->toggleArchiveCampaignId) {
            $campaign = Campaign::readFromId($this->toggleArchiveCampaignId);
            if ($campaign) {
                $campaign->update([
                    'archived' => !$campaign->archived
                ]);
            }
        }

        $search = new SearchArguments(
            sorters: [
                new SearchSorter('archived', SearchSorterDirection::ASC),
                new SearchSorter('key', SearchSorterDirection::ASC)
            ]
        );

        $count = Campaign::count($search);
        $this->pagination = new Pagination([
            'itemCount' => $count,
            'itemsPerPage' => 200,
            'search' => $search
        ]);
        $this->pagination->prepare();
        $this->campaigns = Campaign::search($search, true);
        $this->funnels = Funnel::getInstances();
    }
}
