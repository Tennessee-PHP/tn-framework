<?php

namespace TN\TN_CMS\Controller;

use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;

class PageEntryController extends Controller
{
    #[Path('staff/page-entries')]
    #[Component(\TN\TN_CMS\Component\PageEntry\Admin\ListPageEntries\ListPageEntries::class)]
    #[RoleOnly('content-editor')]
    public function adminListPageEntries(): void {}

    #[Path('staff/page-entries/edit')]
    #[Component(\TN\TN_CMS\Component\PageEntry\Admin\EditPageEntry\EditPageEntry::class)]
    #[RoleOnly('content-editor')]
    public function adminEditPageEntry(): void {}

    #[Path('staff/page-entries/edit/save')]
    #[Component(\TN\TN_CMS\Component\PageEntry\Admin\EditPageEntry\SavePageEntry::class)]
    #[RoleOnly('content-editor')]
    public function adminSavePageEntry(): void {}

    #[Path('cms/page-entries/sitemap')]
    #[Component(\TN\TN_CMS\CLI\PageEntry\Sitemap::class)]
    public function sitemap(): void {}

    #[Path('cms/page-entries/add-from-content-items')]
    #[Component(\TN\TN_CMS\CLI\PageEntry\AddPageEntriesForContentItems::class)]
    public function addPageEntriesForContentItems(): void {}
}