<p>Hi {$username},</p>

<p>You scheduled a change to your {$SITE_NAME} subscription. You will keep your current <b>{$fromPlanName}</b> access until your next renewal on {$effectiveTs|date_format:"%B %e, %Y"}.</p>

<p>On that date your plan will change to <b>{$toPlanName}</b> (billed {$billingCycleName}) and renew for ${$renewalAmount|number_format:2}.</p>

<p>You can review or cancel this scheduled change anytime from your <a href="{$BASE_URL}me/profile/billing">Plans &amp; Payments</a> page.</p>

<p>Thanks for being part of {$SITE_NAME}.</p>
