<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    {include file="TN_Core/Component/Loading/Loading.tpl" 
        title="Loading Type Report" 
        message="Loading Type Report..."
    }

        <div class="reporting-dashboard-component-typereport w-100" id="{$id}"
             data-reportkey="{$reportKey}">

            <p class="alert alert-info"><i class="bi bi-info-circle-fill"></i>
                {$desc}</p>


            <div class="row">
                {if !$disableTimeUnitSelect}
                    <div class="page-control form-group col-12 col-sm-6 col-lg-3">
                        <label>Time Units</label>
                        {$timeUnitSelect->render()}
                    </div>
                {/if}
                <div class="page-control form-group col-12 col-sm-6 col-lg-3">
                    <label>From</label>
                    {$dateInput1->render()}
                </div>
                <div class="page-control form-group col-12 col-sm-6 col-lg-3">
                    <label>To</label>
                    {$dateInput2->render()}
                </div>
                <div class="page-control form-group col-12 col-sm-6 col-lg-3">
                    <label>Compare To</label>
                    {$timeCompareSelect->render()}
                </div>

            </div>

            <div class="row">

                {foreach $filterSelects as $select}
                    <div class="page-control form-group col-12 col-sm-4 col-lg-3">
                        <label>
                            {if $select->requestKey === 'plan'}
                                Plan
                            {elseif $select->requestKey === 'gateway'}
                                Gateway
                            {elseif $select->requestKey === 'billingcycle'}
                                Billing Cycle
                            {elseif $select->requestKey === 'producttype'}
                                Product Type
                            {elseif $select->requestKey === 'refundreason'}
                                Refund Reason
                            {elseif $select->requestKey === 'endedreason'}
                                Ended Reason
                            {elseif $select->requestKey === 'campaign'}
                                Campaign
                            {/if}
                        </label>
                        {$select->render()}
                        {if $select->requestKey !== 'campaign'}
                            <div class="form-check mt-1">
                                <input type="radio" name="breakdown" class="form-check-input"
                                       id="breakdown_{$select->requestKey}"
                                       value="{$select->requestKey}"
                                       {if $select->requestKey|cat:'key' === $breakdown|lower || $select->requestKey|cat:'id' === $breakdown|lower}checked{/if}/>
                                <label for="breakdown_{$select->requestKey}"
                                       class="form-check-label"><small>Breakdown</small></label>
                            </div>
                        {/if}
                    </div>
                {/foreach}

            </div>

            <div class="component-loading d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only"></span>
                </div>
            </div>

            <div class="component-body">

                <div class="mb-4">
                    {$chart->render()}
                </div>

                <div class="mb-4">
                    {$table->render()}
                </div>
            </div>


        </div>
</div>