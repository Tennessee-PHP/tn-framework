<?php

namespace TN\TN_Billing\Attribute\Components\HTMLComponent;

use FBG\TN_Core\Model\User\User;
use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Core\Attribute\Components\HTMLComponent\RequiresResource;
use TN\TN_Core\Component\Renderer\Page\Page;
use TN\TN_Core\Component\Renderer\Page\PageResource;
use TN\TN_Core\Component\Renderer\Page\PageResourceType;

#[\Attribute(\Attribute::TARGET_CLASS)]
class RequiresBraintree extends RequiresResource
{
    public function __construct() {}

    public function addResource(Page $page): void
    {
        $braintree = Gateway::getInstanceByKey('braintree');
        foreach ($braintree->getJsUrls() as $url) {
            $page->addJsUrl($url);
        }
        $user = User::getActive();
        $page->addJsVar('braintreeClientToken', $braintree->generateClientToken($user->loggedIn ? $user : null));

        // Google Pay: TEST for sandbox, PRODUCTION for live; merchant ID optional (omit in sandbox)
        $btEnv = strtolower((string) ($_ENV['BRAINTREE_ENVIRONMENT'] ?? 'sandbox'));
        $page->addJsVar('braintreeEnvironment', $btEnv);
        $page->addJsVar(
            'googlePayEnvironment',
            $btEnv === 'production' ? 'PRODUCTION' : 'TEST'
        );
        $googleMerchantId = trim((string) ($_ENV['GOOGLE_PAY_MERCHANT_ID'] ?? ''));
        if ($googleMerchantId !== '') {
            $page->addJsVar('googlePayMerchantId', $googleMerchantId);
        }
    }
}
