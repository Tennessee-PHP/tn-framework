<?php

namespace TN\TN_Core\Component\Cache\CacheStatus;

use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use \TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Model\Storage\Cache as CacheModel;

#[Page('Cache Status', 'View the status of the website\'s cache', false)]
#[Route('TN_Core:Cache:cacheStatus')]
class CacheStatus extends HTMLComponent
{
    public int $cacheSize;

    public function prepare(): void
    {
        if (isset($_GET['clear_cache'])) {
            CacheModel::deleteAll();
        }
        
        $this->cacheSize = CacheModel::getCacheKeysSize();
        
    }
}