<?php

namespace TN\TN_CMS\Component\LandingPage\LandingPage;

use TN\TN_CMS\Component\PageEntry\Admin\EditPageEntry\EditPageEntry;
use TN\TN_CMS\Model\PageEntry;
use TN\TN_Core\Attribute\Components\HTMLComponent\FullWidth;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use TN\TN_Core\Component\Title\Title;
use TN\TN_Core\Error\ResourceNotFoundException;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_CMS\Model\LandingPage as LandingPageModel;
use TN\TN_Core\Model\User\User;
use \TN\TN_Core\Attribute\Components\Route;

#[Page('Landing Page', '', 'View landing page', true)]
#[Route('TN_CMS:LandingPage:landingPage')]
#[FullWidth]
class LandingPage extends HTMLComponent
{
    public ?LandingPageModel $landingPage;
    public ?PageEntry $pageEntry = null;
    public bool $userIsPageEntryAdmin;
    public EditPageEntry $editPageEntry;

    /**
     * Dynamically match campaign paths by checking if they exist in the database
     * and have useBaseUrl set
     */
    public static function dynamicMatch(HTTPRequest $request): bool|string
    {
        $request = HTTPRequest::get();
        $urlStub = trim($request->path, '/');
        return (LandingPageModel::getPublishedMatchingUrlStub($urlStub) instanceof LandingPageModel);
    }

    public function getContentPageEntry(): ?PageEntry
    {
        return $this->pageEntry;
    }

    public function getPageTitleComponent(array $options): ?Title
    {
        return null;
    }

    public function getPageTitle(): string
    {
        return $this?->landingPage->title ?? '';
    }

    public function getPageDescription(): string
    {
        return $this?->landingPage->description ?? '';
    }

    public function prepare(): void
    {
        $request = HTTPRequest::get();
        $urlStub = trim($request->path, '/');
        $this->landingPage = LandingPageModel::getPublishedMatchingUrlStub($urlStub);
        if (!$this->landingPage) {
            throw new ResourceNotFoundException('Landing page not found');
        }
        $this->pageEntry = PageEntry::getPageEntryForContentItem(LandingPageModel::class, $this->landingPage->id);
        $this->userIsPageEntryAdmin = User::getActive()->hasRole('pageentries-admin');
        if ($this->userIsPageEntryAdmin) {
            $this->editPageEntry = new EditPageEntry(['pageEntryId' => $this->pageEntry->id]);
            $this->editPageEntry->prepare();
        }
    }
}
