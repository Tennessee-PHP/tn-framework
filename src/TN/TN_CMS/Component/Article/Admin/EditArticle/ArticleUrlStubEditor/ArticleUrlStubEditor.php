<?php

namespace TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleUrlStubEditor;

use TN\TN_CMS\Model\Article;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Model\User\User;

#[Reloadable('TN_CMS:Article:adminEditArticleArticleUrlStubEditorReload')]
class ArticleUrlStubEditor extends HTMLComponent
{
    public ?Article $article = null;
    public bool $canEdit;
    public int $statePublished;

    public function prepare(): void
    {
        $this->canEdit = User::getActive()->hasRole('article-editor');
        $this->statePublished = Article::STATE_PUBLISHED;
    }
}