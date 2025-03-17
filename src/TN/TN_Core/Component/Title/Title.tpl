<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        {foreach $breadcrumbEntries as $breadcrumb}
            <li class="breadcrumb-item">
                {if !empty($breadcrumb->path)}<a href="{$breadcrumb->path}">{/if}{$breadcrumb->text}
                    {if !empty($breadcrumb->path)}</a>{/if}
            </li>
        {/foreach}
    </ol>
</nav>
<h1>{$title}</h1>