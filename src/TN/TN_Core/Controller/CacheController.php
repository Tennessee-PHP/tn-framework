<?php

namespace TN\TN_Core\Controller;

use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Component\Renderer\HTML\Redirect;
use TN\TN_Core\Component\Renderer\Renderer;
use TN\TN_Core\Model\Storage\Cache as CacheModel;

class CacheController extends Controller
{
    #[Path('staff/storage/cache')]
    #[Component(\TN\TN_Core\Component\Cache\CacheStatus\CacheStatus::class)]
    #[RoleOnly('tech-admin')]
    public function cacheStatus(): void {}

    #[Path('staff/storage/cache/clear', 'POST')]
    #[RoleOnly('tech-admin')]
    public function clearCache(): Renderer
    {
        CacheModel::deleteAll();
        return Redirect::getInstance(['url' => Controller::path('TN_Core', 'Cache', 'cacheStatus')]);
    }
}