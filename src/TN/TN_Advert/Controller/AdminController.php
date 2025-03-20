<?php

namespace TN\TN_Advert\Controller;

use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;

class AdminController extends Controller
{
    #[Path('adverts/admin/list')]
    #[Component(\TN\TN_Advert\Component\Admin\ListAdverts\ListAdverts::class)]
    #[RoleOnly('advert-editor')]
    public function listAdverts(): void {}

    #[Path('adverts/admin/edit/:id')]
    #[Component(\TN\TN_Advert\Component\Admin\EditAdvert\EditAdvert::class)]
    #[RoleOnly('advert-editor')]
    public function editAdvert(string|int $id): void {}

    #[Path('adverts/admin/save/:id')]
    #[Component(\TN\TN_Advert\Component\Admin\SaveAdvert\SaveAdvert::class)]
    #[RoleOnly('advert-editor')]
    public function saveAdvert(string|int $id): void {}
}