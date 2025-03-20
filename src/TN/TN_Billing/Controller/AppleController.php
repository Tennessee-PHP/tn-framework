<?php

namespace TN\TN_Billing\Controller;

use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Controller\Controller;

class AppleController extends Controller
{
    #[Path('api/apple/subscription')]
    #[Component(\TN\TN_Billing\Component\Apple\Api\Subscription::class)]
    public function apiSubscription() {}

}