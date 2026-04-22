<p><strong>{$assignerDisplayName|escape}</strong> assigned you a misc job:</p>

<p style="font-size:1.05em;"><strong>{$jobTitle|escape}</strong></p>

{if $dueLine}
<p>{$dueLine|escape}</p>
{/if}

<p><a href="{$jobsUrl|escape:'url'}">Open Jobs</a></p>

{include file="TN_Core/Model/Email/Template/Ne/_Partials/PreferencesFooter.tpl"}
