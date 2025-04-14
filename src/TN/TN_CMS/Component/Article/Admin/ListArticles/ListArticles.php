<?php

namespace TN\TN_CMS\Component\Article\Admin\ListArticles;

use TN\TN_CMS\Component\Article\Admin\ArticleStateSelect\ArticleStateSelect;
use TN\TN_CMS\Model\Article;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Component\User\Select\UserSelect\UserSelect;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\User\User;
use \TN\TN_Core\Attribute\Components\Route;

#[Page('Edit Articles List', 'List of editable articles', false)]
#[Route('TN_CMS:Article:adminListArticles')]
#[Reloadable]
class ListArticles extends HTMLComponent
{
    public array $articles;
    public array $templates;
    public Article $article;
    public ?string $sortProperty = null;
    public ?int $sortDirection = null;
    public UserSelect $userSelect;
    public ArticleStateSelect $articleStateSelect;
    public Pagination $pagination;
    public bool $isArticleAuthor = false;
    public bool $isArticleEditor = false;
    public bool $isBackendArticleListViewer = false;
    public int $stateDraft;
    public int $stateReadyForEditing;
    public int $stateTemplate;
    public int $statePublished;

    public function prepare(): void
    {
        $this->userSelect = new UserSelect([
            'users' => User::getUsersWithRole('article-author'),
            'allLabel' => 'All Authors',
            'displayProperty' => 'name'
        ]);
        $this->userSelect->prepare();
        $this->articleStateSelect = new ArticleStateSelect();
        $this->articleStateSelect->prepare();

        $this->isArticleEditor = User::getActive()->hasRole('article-editor');
        $this->isBackendArticleListViewer = User::getActive()->hasRole('backend-article-list-viewer');
        $this->isArticleAuthor = User::getActive()->hasRole('article-author');

        $this->stateDraft = Article::STATE_DRAFT;
        $this->stateReadyForEditing = Article::STATE_READY_FOR_EDITING;
        $this->statePublished = Article::STATE_PUBLISHED;
        $this->stateTemplate = Article::STATE_TEMPLATE;

        $this->templates = Article::getArticles(
            ['authorId' => User::getActive()->id, 'state' => Article::STATE_TEMPLATE],
            $this->sortProperty,
            $this->sortDirection
        );

        $search = new SearchArguments();
        $filters = [];
        if ($this->articleStateSelect->selected && !$this->articleStateSelect->selected->all) {
            $filters['state'] = $this->articleStateSelect->selected->key;
        }
        if ($this->isArticleEditor || $this->isBackendArticleListViewer) {
            if ($this->userSelect->selected->id) {
                $filters['authorId'] = $this->userSelect->selected->id;
            }
        } else {
            $filters['authorId'] = User::getActive()->id;
        }

        $count = Article::getArticles($filters, null, null, 0, 100, true);
        $this->pagination = new Pagination([
            'itemCount' => $count,
            'itemsPerPage' => 50,
            'search' => $search
        ]);
        $this->pagination->prepare();

        $this->articles = Article::getArticles($filters, null, null, $this->pagination->queryStart, $this->pagination->itemsPerPage);
    }
}
