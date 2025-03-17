<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard;

use TN\TN_Core\Attribute\Components\FromRequest;
use TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Attribute\Components\HTMLComponent\RequiresChartJS;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use TN\TN_Core\Attribute\Components\Route;

#[Page('Analytics Dashboard', '', false)]
#[Route('TN_Reporting:Analytics:dashboard')]
#[RequiresChartJS]
#[Reloadable('TN_Reporting:Analytics:reloadDashboard')]
class Dashboard extends HTMLComponent
{
    #[FromRequest] public string $reportkey = '';
    public DashboardComponent $report;

    public function prepare(): void
    {
        $this->report = DashboardComponent::getDashboardComponentByKey($this->reportkey);
        $this->report->prepare();
    }
}