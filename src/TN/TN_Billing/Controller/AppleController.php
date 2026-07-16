<?php

namespace TN\TN_Billing\Controller;

use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Access\Restrictions\Anyone;
use TN\TN_Core\Attribute\Route\AllowOrigin;
use TN\TN_Core\Attribute\Route\AllowCredentials;

class AppleController extends Controller
{
    #[Path('api/apple/subscription')]
    #[Anyone]
    #[AllowOrigin]
    #[AllowCredentials]
    #[Component(\TN\TN_Billing\Component\Apple\Api\Subscription::class)]
    public function apiSubscription() {}
}
