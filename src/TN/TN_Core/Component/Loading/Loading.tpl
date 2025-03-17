<div class="component-loading position-fixed top-50 start-50 translate-middle text-center w-100 d-none" style="z-index: 1050;">
    <div class="bg-white bg-opacity-75 p-4 mx-auto rounded-3 border border-light" style="max-width: 400px;">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">{$title|default:'Loading...'}</span>
        </div>
        <div class="loading-message h5 mb-2">{$message|default:'Loading...'}</div>
        {if $extra}
            {$extra}
        {/if}
    </div>
</div> 