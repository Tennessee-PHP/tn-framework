<div class="reporting-dashboard-component-dashboardsummary-summaryblock card-body p-0">

    <div class="card-header d-flex align-items-center"><div class="flex-fill">{$title}</div><a class="btn btn-primary" href="{$BASE_URL}staff/reporting/dashboard?reportkey={$reportKey}">More <i class="bi bi-arrow-right"></i>
         </a></div>
    <div>{$dial->render()}</div>

    <div>{$chart->render()}</div>

</div>