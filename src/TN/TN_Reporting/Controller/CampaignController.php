<?php

namespace TN\TN_Reporting\Controller;

use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;

class CampaignController extends Controller
{
    #[Path('staff/campaigns')]
    #[Component(\TN\TN_Reporting\Component\Campaign\ListCampaigns\ListCampaigns::class)]
    #[RoleOnly('marketing-admin')]
    public function listCampaigns(): void {}

    #[Path('staff/campaigns/new')]
    #[Path('staff/campaigns/edit/:id')]
    #[Path('staff/campaigns/edit/:id/deactivate')]
    #[Component(\TN\TN_Reporting\Component\Campaign\EditCampaign\EditCampaign::class)]
    #[RoleOnly('marketing-admin')]
    public function editCampaign(): void {}

    #[Path('staff/campaigns/save')]
    #[Component(\TN\TN_Reporting\Component\Campaign\EditCampaign\SaveCampaign::class)]
    #[RoleOnly('marketing-admin')]
    public function saveCampaign(): void {}
}
