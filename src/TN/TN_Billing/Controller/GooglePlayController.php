<?php

namespace TN\TN_Billing\Controller;

use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Controller\Controller;

use TN\TN_Core\Attribute\Route\Access\Restrictions\Anyone;
use TN\TN_Core\Attribute\Route\AllowOrigin;
use TN\TN_Core\Attribute\Route\AllowCredentials;

class GooglePlayController extends Controller
{
    #[Path('api/googleplay/subscription')]
    #[Anyone]
    #[AllowOrigin]
    #[AllowCredentials]
    #[Component(\TN\TN_Billing\Component\GooglePlay\Api\Subscription::class)]
    public function apiSubscription() {}
}
