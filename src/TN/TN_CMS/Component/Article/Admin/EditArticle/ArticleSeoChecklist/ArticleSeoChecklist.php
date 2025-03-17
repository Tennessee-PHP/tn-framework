<?php

namespace TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleSeoChecklist;

use TN\TN_CMS\Model\Article;
use TN\TN_Core\Component\HTMLComponent;

class ArticleSeoChecklist extends HTMLComponent {

    public ?Article $article = null;

    public function prepare(): void
    {
        $this->article->primarySeoKeyword =  str_replace('-', ' ', $this->article->primarySeoKeyword);
    }
}