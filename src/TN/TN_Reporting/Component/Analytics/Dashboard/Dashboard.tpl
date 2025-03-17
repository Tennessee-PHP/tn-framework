<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}" data-reportkey="{$reportkey}">
    <div class="mt-5 mb-5 d-flex align-items-start">

        <ul class="nav nav-pills flex-column me-3">
            <li class="nav-item">
                <a class="nav-link{if !$report->reportKey} active{/if}" aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard"><i class="bi bi-speedometer"></i>
                    Summary</a>
            </li>
            <li><h3><i class="bi bi-coin"></i>
                    Revenue</h3></li>
            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'dailyRevenue'} active{/if}" aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=dailyRevenue">Daily</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'recurringRevenue'} active{/if}"
                   aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=recurringRevenue">Annual Run Rate</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'revenuePerSubscription'} active{/if}"
                   aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=revenuePerSubscription">Per Subscription</a>
            </li>

            <li><h3><i class="bi bi-credit-card-2-front-fill"></i>
                    Subscriptions</h3></li>
            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'active'} active{/if}" aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=active">Active</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'newSubscriptions'} active{/if}" aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=newSubscriptions">New</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'renewalSubscriptions'} active{/if}" aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=renewalSubscriptions">Renewed</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'endedSubscriptions'} active{/if}" aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=endedSubscriptions">Ended</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'stalledSubscriptions'} active{/if}" aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=stalledSubscriptions">Stalled</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'churn'} active{/if}" aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=churn">Churn</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'subscriptionLifetimeValue'} active{/if}"
                   aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=subscriptionLifetimeValue">Lifetime Value</a>
            </li>

            <li><h3><i class="bi bi-bank2"></i>
                    Expenses</h3></li>
            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'expensesFees'} active{/if}" aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=expensesFees">Fees</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'expensesRefunds'} active{/if}" aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=expensesRefunds">Refunds</a>
            </li>

            <li><h3><i class="bi bi-people-fill"></i>
                    Users</h3></li>
            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'userRegistrations'} active{/if}" aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=userRegistrations">Registrations</a>
            </li>

            {*<li><h3><i class="bi bi-graph-up-arrow"></i>
                    Page Views</h3></li>*}

            <li><h3><i class="bi bi-envelope-open"></i>
                    Email List</h3></li>

            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'emailListActive'} active{/if}" aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=emailListActive">Active</a>
            </li>

            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'emailListCancellations'} active{/if}" aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=emailListCancellations">Cancellations</a>
            </li>

            <li class="nav-item">
                <a class="nav-link{if $report->reportKey === 'emailListNew'} active{/if}" aria-current="page"
                   href="{$BASE_URL}staff/reporting/dashboard?reportkey=emailListNew">New</a>
            </li>
        </ul>


        {$report->render()}
    </div>
</div>