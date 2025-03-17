<div class="reporting-dashboard-component-dashboardsummary title-embed flex-fill">

        <div class="row g-0">
            {foreach $summaryBlocks as $block}
            <div class="col col-12 col-md-{$block['md-cols']} p-2">
                <div class="card card-ps p-0">
                    {$block['component']->render()}
                </div>
            </div>
            {/foreach}
        </div>
</div>