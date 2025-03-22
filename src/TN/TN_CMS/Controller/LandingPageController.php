<?php

namespace TN\TN_CMS\Controller;

use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Access\Restrictions\Anyone;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;
use TN\TN_Core\Attribute\Route\DynamicPath;

class LandingPageController extends Controller
{
    #[Path('staff/landing-pages')]
    #[Component(\TN\TN_CMS\Component\LandingPage\Admin\ListLandingPages\ListLandingPages::class)]
    #[RoleOnly('content-editor')]
    public function adminListLandingPages(): void {}

    #[Path('staff/landing-pages/edit')]
    #[Component(\TN\TN_CMS\Component\LandingPage\Admin\EditLandingPage\EditLandingPage::class)]
    #[RoleOnly('content-editor')]
    public function adminEditLandingPage(): void {}

    #[Path('staff/landing-pages/edit/save')]
    #[Component(\TN\TN_CMS\Component\LandingPage\Admin\EditLandingPage\EditLandingPageProperties::class)]
    #[RoleOnly('content-editor')]
    public function adminEditLandingPageProperties(): void {}

    #[Path('landing-page/:urlStub')]
    #[DynamicPath(\TN\TN_CMS\Component\LandingPage\LandingPage\LandingPage::class, 'dynamicMatch')]
    #[Component(\TN\TN_CMS\Component\LandingPage\LandingPage\LandingPage::class)]
    #[Anyone]
    public function landingPage(): void {}
}
