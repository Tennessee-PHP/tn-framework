<?php

namespace TN\TN_Reporting\Component\Campaign\EditCampaign;

use TN\TN_Billing\Model\VoucherCode;
use TN\TN_Core\Attribute\Components\HTMLComponent\RequiresTinyMCE;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use TN\TN_Core\Error\ResourceNotFoundException;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Reporting\Component\Campaign\ListCampaigns\ListCampaigns;
use TN\TN_Reporting\Model\Campaign\Campaign;
use TN\TN_Reporting\Model\Funnel\Funnel;
use TN\TN_Core\Attribute\Components\Route;

#[Page('Edit A Campaign', '', false)]
#[Route('TN_Reporting:Campaign:editCampaign')]
#[Breadcrumb(ListCampaigns::class)]
#[RequiresTinyMCE]
class EditCampaign extends HTMLComponent
{
    public ?int $id = null;
    public ?Campaign $campaign;
    public array $vouchers;
    public array $funnels;
    public bool $isPhantom;

    public function getPageTitle(): string
    {
        return $this->isPhantom ? 'Edit A Campaign' : 'Create A New Campaign';
    }

    public function prepare(): void
    {
        $this->campaign = $this->id ? Campaign::readFromId($this->id) : Campaign::getInstance();
        $this->isPhantom = $this->id !== null;
        if (!$this->campaign) {
            throw new ResourceNotFoundException('campaign');
        }
        $this->vouchers = VoucherCode::search(new SearchArguments());
        $this->funnels = Funnel::getInstances();
    }
}
