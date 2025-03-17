<?php

namespace TN\TN_Core\Controller;

use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;

class Cache extends Controller
{
    #[Path('staff/storage/cache')]
    #[Component(\TN\TN_Core\Component\Cache\CacheStatus\CacheStatus::class)]
    #[RoleOnly('tech-admin')]
    public function cacheStatus(): void {}
}