<?php

namespace TN\TN_CMS\Component\Article\Admin\EditArticle;

use TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleMetadataEditor\ArticleMetadataEditor;
use TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleSeoChecklist\ArticleSeoChecklist;
use TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleStatusEditor\ArticleStatusEditor;
use TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleThumbnailEditor\ArticleThumbnailEditor;
use TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleTitleEditor\ArticleTitleEditor;
use TN\TN_CMS\Component\Article\Admin\ListArticles\ListArticles;
use TN\TN_CMS\Component\TagEditor\TagEditor;
use TN\TN_CMS\Model\Article;
use TN\TN_CMS\Model\Category;
use TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use TN\TN_Core\Attribute\Components\HTMLComponent\RequiresTinyMCE;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Component\Title\Title;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;
use \TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Model\Package\Stack;

#[Page('Edit Article', '', false)]
#[Route('TN_CMS:Article:adminEditArticle')]
#[Breadcrumb(ListArticles::class)]
#[RequiresTinyMCE]
class EditArticle extends HTMLComponent
{
    public bool $userIsArticleEditor = false;
    public Article $article;
    public ArticleStatusEditor $articleStatusEditor;
    public ArticleTitleEditor $articleTitleEditor;
    public ArticleSeoChecklist $articleSeoChecklist;
    public ArticleMetadataEditor $articleMetadataEditor;
    public ArticleThumbnailEditor $articleThumbnailEditor;
    public TagEditor $tagEditor;
    public array $categories;
    public int $statePublished;

    public function getPageTitleComponent(array $options): ?Title
    {
        return null;
    }

    public function prepare(): void
    {
        $this->userIsArticleEditor = User::getActive()->hasRole('article-editor');
        $this->statePublished = Article::STATE_PUBLISHED;
        $articleId = (int)$_GET['articleid'] ?? null;
        if ($articleId) {
            $article = Article::getContentItem($articleId);
            if (!$article) {
                throw new ValidationException('Article does not exist');
            }
            $this->article = $article;
        } else if (!empty(($_GET['fromtemplateid']))) {
            $this->article = Article::getInstance();
            $this->article->copyFromTemplate(Article::getContentItem($_GET['fromtemplateid']));
        } else {
            $this->article = Article::getInstance();
            $this->initializeNewArticle();
        }
        if (!$this->article->userCanEdit(User::getActive())) {
            throw new ValidationException('Cannot edit article');
        }

        $this->articleTitleEditor = new (Stack::resolveClassName(ArticleTitleEditor::class))([
            'article' => $this->article
        ]);
        $this->articleTitleEditor->prepare();
        $this->articleStatusEditor = new (Stack::resolveClassName(ArticleStatusEditor::class))(['article' => $this->article]);
        $this->articleStatusEditor->prepare();
        $this->articleSeoChecklist = new (Stack::resolveClassName(ArticleSeoChecklist::class))(['article' => $this->article]);
        $this->articleSeoChecklist->prepare();
        $this->articleMetadataEditor = new (Stack::resolveClassName(ArticleMetadataEditor::class))(['article' => $this->article]);
        $this->articleMetadataEditor->prepare();
        $this->articleThumbnailEditor = new (Stack::resolveClassName(ArticleThumbnailEditor::class))(['article' => $this->article]);
        $this->articleThumbnailEditor->prepare();
        $this->tagEditor = new TagEditor($this->article);
        $this->tagEditor->prepare();

        $this->categories = Category::getAll();
    }

    protected function initializeNewArticle(): void
    {
        $this->article->publishedTs = Time::getNow();
        $author = User::getActive();
        $this->article->authorId = $author->id;
        $this->article->numTags = 0;
        $this->article->authorAvatarUrl = $this->article->getAvatarUrl($author instanceof User ? $author : null);
        $this->article->year = date('Y');
        $this->article->week = 0;
    }

    public function editProperties(array $data): void
    {
        // data is in effect the post array.
        // add validations to each property and filter down the $data array
        $edits = [];

        foreach ($data as $property => $value) {
            switch ($property) {
                case 'tags':
                    if (!isset($this->article->id)) {
                        $edits['title'] = $this->article->title; // force a save to make sure we have an ID
                    }
                    break;
                case 'title':
                    $edits['title'] = $value;
                    break;
                case 'description':
                    $edits['description'] = $value;
                    break;
                case 'publishedTs':
                    $edits['publishedTs'] = strtotime($value);
                    break;
                case 'authorId':
                    $edits['authorId'] = is_numeric($value) ? intval($value) : 0;
                    break;
                case 'primarySeoKeyword':
                    $edits['primarySeoKeyword'] = $value;
                    break;
                case 'contentRequired':
                    $edits['contentRequired'] = $value;
                    break;
                case 'content':
                    $edits['content'] = $value;
                    break;
                case 'weight':
                    $this->canEdit = User::getActive()->hasRole('article-editor');
                    if ($this->canEdit) {
                        $edits['weight'] = $value;
                    }
                    break;
                case 'urlStub':
                    if ($this->article->state !== Article::STATE_PUBLISHED) {
                        $edits['urlStub'] = $value;
                    }
                    break;
                case 'week':
                    $edits['week'] = $value;
                    break;
                case 'year':
                    $edits['year'] = $value;
                    break;
                case 'thumbnailSrc':
                    $edits['thumbnailSrc'] = $value;
                    break;
                default:
                    break;
            }
        }

        if (!empty($edits)) {
            $this->article->update($edits);
        }

        if (isset($data['tags'])) {
            $this->tagEditor->contentId = $this->article->id;
            $this->tagEditor->updateTags(json_decode($data['tags'], true));
        }
    }
}
