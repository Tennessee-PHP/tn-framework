<?php

namespace TN\TN_CMS\Component\Article\Article;

use TN\TN_CMS\Component\PageEntry\Admin\EditPageEntry\EditPageEntry;
use TN\TN_CMS\Model\PageEntry;
use TN\TN_Core\Attribute\Components\FromPath;
use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Attribute\Route\Access\Restrictions\ContentOwnersOnly;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Component\Title\Title;
use TN\TN_Core\Error\Access\AccessForbiddenException;
use TN\TN_Core\Error\Access\AccessLoginRequiredException;
use TN\TN_Core\Error\Access\AccessUncontrolledException;
use TN\TN_Core\Error\Access\UnmatchedException;
use TN\TN_Core\Error\ResourceNotFoundException;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;
use TN\TN_CMS\Model\Article as ArticleModel;
use \TN\TN_Core\Attribute\Components\Route;

#[Page('Article', 'Description', true)]
#[Route('TN_CMS:Article:article')]
class Article extends HTMLComponent
{
    #[FromPath] public string|int $urlStub;
    #[FromQuery] public bool $preview = false;
    public ?PageEntry $pageEntry = null;
    public bool $userIsPageEntryAdmin;
    public EditPageEntry $editPageEntry;
    public ArticleModel $article;
    public array $tags = [];

    /**
     * @throws ValidationException
     * @throws AccessLoginRequiredException
     * @throws AccessForbiddenException
     * @throws ResourceNotFoundException
     * @throws AccessUncontrolledException
     * @throws UnmatchedException
     */
    public function prepare(): void
    {
        $request = HTTPRequest::get();

        if (!isset($this->urlStub)) {
            $this->urlStub = $_GET['article'];
        }

        $article = ArticleModel::readFromUrlStub($this->urlStub);

        if (!$article) {
            throw new ResourceNotFoundException('Article not found');
        }

        $canView = $article->state === ArticleModel::STATE_PUBLISHED && $article->publishedTs < Time::getNow();

        if (!$canView && $this->preview) {
            $user = User::getActive();
            $canView = $article->userCanEdit($user) || $user->hasRole('backend-article-list-viewer');
        }

        if (!$canView) {
            throw new ValidationException('Article is not yet published');
        }

        $this->article = $article;

        $request->setAccess(new ContentOwnersOnly($this->article->contentRequired));

        $this->pageEntry = PageEntry::getPageEntryForContentItem(ArticleModel::class, $this->article->id);
        $this->userIsPageEntryAdmin = User::getActive()->hasRole('pageentries-admin');
        if ($this->userIsPageEntryAdmin) {
            $this->editPageEntry = new EditPageEntry(['pageEntryId' => $this->pageEntry->id]);
            $this->editPageEntry->prepare();
        }

        // Populate tags for the article
        $this->tags = \TN\TN_CMS\Model\Tag\TaggedContent::getFromContentItem(ArticleModel::class, $this->article->id);
    }

    public function getContentPageEntry(): ?PageEntry
    {
        return $this->pageEntry;
    }

    public function getPageTitle(): string
    {
        return $this?->article->title ?? '';
    }

    public function getPageSubtitle(): ?string
    {
        return $this?->article->description ?? null;
    }

    public function getPageDescription(): string
    {
        return $this?->article->description ?? '';
    }
}
