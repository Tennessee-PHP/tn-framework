<div class="reporting-dashboard-component-dashboardsummary-dashboarddial align-center d-flex justify-content-center p-2">
    <p class="fs-3 text-primary flex-fill"><b>{$prefix}{$now|number_format:$decimals}{$suffix}</b></p>
    {include "./DashboardDialComparison.tpl" label="Last Year" pcDiff=$yearPcDiff value=$year positive=$yearDiffPositive}
    {include "./DashboardDialComparison.tpl" label="Last Season" pcDiff=$seasonPcDiff value=$season positive=$seasonDiffPositive}
</div>