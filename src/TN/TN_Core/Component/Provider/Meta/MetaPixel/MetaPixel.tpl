{literal}
<script>
    !function (f, b, e, v, n, t, s) {
        if (f.fbq) return;
        n = f.fbq = function () {
            n.callMethod ?
                n.callMethod.apply(n, arguments) : n.queue.push(arguments)
        };
        if (!f._fbq) f._fbq = n;
        n.push = n;
        n.loaded = !0;
        n.version = '2.0';
        n.queue = [];
        t = b.createElement(e);
        t.async = !0;
        t.src = v;
        s = b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t, s)
    }(window, document, 'script',
        'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '295249791034394');
    {/literal}

    {*fbq('track', 'PageView');*}
    {foreach $events as $event}
    fbq('track', '{$event->event}'{foreach $event->args as $arg}, {json_encode($arg)}{/foreach});
    {/foreach}
    {literal}
</script>
    <noscript>
        <img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id=295249791034394&ev=PageView&noscript=1">
    </noscript>

{/literal}

{* new one that doesn't work

<!-- Meta Pixel Code -->
<script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '295249791034394');
        {/literal}
        {foreach $events as $event}
        fbq('{$event->event}'{foreach $event->args as $arg}, {json_encode($arg)}{/foreach});
        {/foreach}
        {literal}
    </script>
    <noscript>
        <img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=295249791034394&ev=PageView&noscript=1"/>
    </noscript>
<!-- End Meta Pixel Code -->

*}
