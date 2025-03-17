<?php

namespace TN\TN_CMS\Component\Article\Admin\EditArticle;

use TN\TN_Core\Component\Renderer\JSON\JSON;

class SaveProperties extends JSON {
    public function prepare(): void
    {
        $editArticle = new EditArticle(['articleId' => $_GET['articleid'] ?? null ]);
        $editArticle->prepare();
        $editArticle->editProperties($_POST);
        $this->data = [
            'result' => 'success',
            'message' => 'Article properties saved',
            'articleId' => $editArticle->article->editId,
            'articleUrl' => $editArticle->article->url
        ];
    }
}