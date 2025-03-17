{strip}
    <script>
        var TN = TN || {};
        {foreach $envList as $env}
        TN.{$env@key} = "{$env}";
        {/foreach}
        {foreach $jsVars as $var}
        TN.{$var@key} = {$var|json_encode};
        {/foreach}
    </script>
{/strip}