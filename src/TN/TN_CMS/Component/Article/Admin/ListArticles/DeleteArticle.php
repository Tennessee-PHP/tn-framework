<?php

namespace TN\TN_CMS\Component\Article\Admin\ListArticles;

use TN\TN_CMS\Model\Article;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Model\User\User;

class DeleteArticle extends JSON
{
    public function prepare(): void
    {
        if (isset($_POST['delete']) && (bool)$_POST['delete'] === true) {
            $id = intval($_POST['articleId']);
            $article = Article::getContentItem($id);
            if (User::getActive()->hasRole('article-editor') || $article->authorId === User::getActive()->id) {
                $article->erase();
                $this->data = [
                    'result' => 'success',
                    'message' => 'Article deleted'
                ];
            }
        }
    }
}
