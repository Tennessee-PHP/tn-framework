<?php

namespace TN\TN_Core\Component\Title;

use TN\TN_CMS\Component\PageEntry\Admin\EditPageEntry\EditPageEntry;
use TN\TN_CMS\Model\PageEntry;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Model\User\User;

class Title extends HTMLComponent {
    public ?string $title = null;
    public ?string $subtitle = null;
    /** @var BreadcrumbEntry[] each entry is either a string that'll display without a link, or a component class to use for a title and path */
    public array $breadcrumbEntries;
    public ?PageEntry $pageEntry = null;
    public bool $userIsPageEntryAdmin = false;
    public EditPageEntry $editPageEntry;

    public function prepare(): void
    {
        foreach ($this->breadcrumbEntries as &$breadcrumb) {
            $breadcrumb->prepare();
        }
        if ($this->pageEntry) {
            $this->userIsPageEntryAdmin = User::getActive()->hasRole('pageentries-admin');
            $this->editPageEntry = new EditPageEntry(['pageEntryId' => $this->pageEntry->id]);
            $this->editPageEntry->prepare();
        }
    }
}