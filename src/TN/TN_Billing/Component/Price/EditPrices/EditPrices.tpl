<div class="{$classAttribute}" id="{$idAttribute}">
    <form id="change_prices_form">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>Plan</th>
                    {foreach $billingCycles as $cycle}
                        <th scope="col">{$cycle->name}</th>
                    {/foreach}
                </tr>
                </thead>
                <tbody>
                {foreach $plans as $plan}
                    <tr>
                        {if $plan->paid}
                            <td>{$plan->name}</td>
                            {foreach $plan->getAllPrices() as $price}
                                <td><input type="number" class="form-control" step=".01" name="{$plan->key}-{$price->billingCycleKey}"
                                           value="{$price->price}"></td>
                            {/foreach}
                        {/if}
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            <input
                    type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#change_price_modal"
                    value="Change Prices"
            >
        </div>
        {*Confirm Price Changes Modal*}
        <div class="modal fade" id="change_price_modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Changes</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Changing prices is a big deal.
                        <b>Are you sure?</b>
                    </div>
                    <div class="modal-footer d-flex justify-content-center">
                        <input type="submit" class="btn btn-primary" value="Confirm my changes">
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>