<?php

namespace TN\TN_Billing\Controller;

use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Controller\Controller;

class GooglePlayController extends Controller
{
    #[Path('api/googleplay/subscription')]
    #[Component(\TN\TN_Billing\Component\GooglePlay\Api\Subscription::class)]
    public function apiSubscription() {}

}