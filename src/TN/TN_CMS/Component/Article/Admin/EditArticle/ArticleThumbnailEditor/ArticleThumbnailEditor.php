<?php

namespace TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleThumbnailEditor;

use TN\TN_CMS\Model\Article;
use TN\TN_Core\Attribute\Components\FromRequest;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Attribute\Components\Route;

#[Reloadable]
#[Route('TN_CMS:Article:adminEditArticleArticleThumbnailEditor')]
class ArticleThumbnailEditor extends HTMLComponent
{
    public ?Article $article = null;
    #[FromRequest] public string|int|null $articleId = null;
    public array $candidateImgSrcs = [];

    public function prepare(): void
    {
        if (!$this->article) {
            if ($this->articleId === 'new') {
                $this->article = Article::getInstance();
            } else {
                $this->article = Article::readFromId($this->articleId);
            }
        }

        $this->candidateImgSrcs = [];
        if (isset($_REQUEST['imgSrcs']) && !empty($_REQUEST['imgSrcs'])) {
            $imgSrcsRaw = explode('|', $_REQUEST['imgSrcs']);
            foreach ($imgSrcsRaw as $imgSrc) {
                if (!str_starts_with($imgSrc, 'blob:')) {
                    $this->candidateImgSrcs[] = $imgSrc;
                }
            }
        }

        if (str_starts_with($this->article->thumbnailSrc, 'blob:')) {
            $this->article->thumbnailSrc = '';
        }

        // if the article's current imageUrl is NOT in candidateImgSrcs, add it
        if (!in_array($this->article->thumbnailSrc, $this->candidateImgSrcs) && !empty($this->article->thumbnailSrc)) {
            $this->candidateImgSrcs[] = $this->article->thumbnailSrc;
        }

        //  if you have candidateImgSrcs BUT the article does NOT currently have a thumbnail, set the article's imageUrl to be the first candidateImgSrc
        if (!empty($this->candidateImgSrcs) && empty($this->article->thumbnailSrc)) {
            $this->article->update(['thumbnailSrc' => $this->candidateImgSrcs[0]]);
        }
    }
}
