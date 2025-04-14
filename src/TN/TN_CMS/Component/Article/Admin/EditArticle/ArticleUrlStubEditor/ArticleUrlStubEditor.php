<?php

namespace TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleUrlStubEditor;

use TN\TN_CMS\Model\Article;
use TN\TN_Core\Attribute\Components\FromRequest;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Model\User\User;

#[Reloadable]
#[Route('TN_CMS:Article:adminEditArticleArticleUrlStubEditor')]
class ArticleUrlStubEditor extends HTMLComponent
{
    public ?Article $article = null;
    public bool $canEdit;
    public int $statePublished;
    #[FromRequest] public ?int $articleId = null;

    public function prepare(): void
    {
        if (!$this->article && $this->articleId) {
            $this->article = Article::readFromId($this->articleId);
        }
        $this->canEdit = User::getActive()->hasRole('article-editor');
        $this->statePublished = Article::STATE_PUBLISHED;
    }
}
