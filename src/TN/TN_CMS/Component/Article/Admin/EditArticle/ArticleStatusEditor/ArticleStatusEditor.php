<?php

namespace TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleStatusEditor;

use TN\TN_CMS\Model\Article;
use TN\TN_Core\Attribute\Components\FromRequest;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Time\Time;

#[Reloadable]
#[Route('TN_CMS:Article:adminEditArticleArticleStatusEditor')]
class ArticleStatusEditor extends HTMLComponent
{
    public ?Article $article = null;
    #[FromRequest] public string|int|null $articleId = null;
    public int $stateDraft;
    public int $stateReadyForEditing;
    public int $statePublished;
    public int $stateTemplate;

    public function prepare(): void
    {
        $this->stateDraft = Article::STATE_DRAFT;
        $this->stateReadyForEditing = Article::STATE_READY_FOR_EDITING;
        $this->statePublished = Article::STATE_PUBLISHED;
        $this->stateTemplate = Article::STATE_TEMPLATE;

        if (!$this->article) {
            if ($this->articleId === 'new') {
                $this->article = Article::getInstance();
            } else {
                $this->article = Article::readFromId($this->articleId);
            }
        }

        if (isset($_REQUEST['status'])) {
            $newStatus = match ($_REQUEST['status']) {
                'draft' => Article::STATE_DRAFT,
                'editor' => Article::STATE_READY_FOR_EDITING,
                'publish' => Article::STATE_PUBLISHED,
                'template' => Article::STATE_TEMPLATE,

                default => 0
            };

            $updateData = ['state' => $newStatus];

            if (isset($_REQUEST['setpublishtstonow']) && $_REQUEST['setpublishtstonow'] == 1) {
                $updateData['publishedTs'] = Time::getNow();
            }


            // post request is asking to make it a template, but it's currently a published article
            if ($newStatus == 'template' && $this->article->state == Article::STATE_PUBLISHED) {
                throw new ValidationException('Cannot make a published article into a template');
            }

            if ($newStatus === Article::STATE_READY_FOR_EDITING) {
                $this->article->checkReadyForEditing();
            }

            if ($newStatus === Article::STATE_PUBLISHED) {
                $this->article->checkReadyForPublishing();
            }

            $this->article->update($updateData);
        }
    }
}
