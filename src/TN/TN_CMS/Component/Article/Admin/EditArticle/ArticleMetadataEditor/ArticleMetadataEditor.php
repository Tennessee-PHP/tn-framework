<?php

namespace TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleMetadataEditor;

use TN\TN_Billing\Model\Subscription\Content\Content;
use TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleUrlStubEditor\ArticleUrlStubEditor;
use TN\TN_CMS\Model\Article;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Model\User\User;

class ArticleMetadataEditor extends HTMLComponent {
    public Article $article;
    public ?object $weekBySeasonSelect = null;
    public ArticleUrlStubEditor $articleUrlStubEditor;
    public bool $canEdit = false;
    public array $contentOptions = [];

    public function prepare(): void
    {
        $this->canEdit = User::getActive()->hasRole('article-editor');
        $this->articleUrlStubEditor = new ArticleUrlStubEditor([
            'article' => $this->article
        ]);
        $this->articleUrlStubEditor->prepare();
        $this->contentOptions = Content::getInstances();
    }
}