<?php

namespace TN\TN_CMS\Component\Article\Admin\ListArticles;

use TN\TN_CMS\Model\Article;
use TN\TN_Core\Component\Renderer\JSON\JSON;

class EditArticleWeight extends JSON
{
    public function prepare(): void
    {
        if (isset($_POST['weight']) && !empty($_POST['weight'])) {
            $id = intval($_POST['articleId']);
            $weight = $_POST['weight'];

            $article = Article::getContentItem($id);
            $article->update(['weight' => $weight]);
        }
        $this->data = [
            'result' => 'success',
            'message' => 'Article weight updated'
        ];
    }
}