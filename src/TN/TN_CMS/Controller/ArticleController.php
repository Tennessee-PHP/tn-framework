<?php

namespace TN\TN_CMS\Controller;

use TN\TN_Core\Attribute\Route\JSON;
use TN\TN_Core\Attribute\Route\Text;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Access\Restrictions\Anyone;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RolesOnly;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;
use TN\TN_Core\Attribute\Route\Access\Restrictions\UsersOnly;

class ArticleController extends Controller
{
    #[Path('articles')]
    #[Component(\TN\TN_CMS\Component\Article\ListArticles\ListArticles::class)]
    #[Anyone]
    public function listArticles(): void {}

    #[Path('article/:urlStub')]
    #[Component(\TN\TN_CMS\Component\Article\Article\Article::class)]
    #[Anyone]
    public function article(): void {}

    #[Path('staff/articles')]
    #[Component(\TN\TN_CMS\Component\Article\Admin\ListArticles\ListArticles::class)]
    #[RolesOnly(['backend-article-list-viewer', 'article-editor', 'article-author'])]
    public function adminListArticles(): void {}

    #[Path('staff/articles/edit-article-weight')]
    #[Component(\TN\TN_CMS\Component\Article\Admin\ListArticles\EditArticleWeight::class)]
    #[RoleOnly('article-editor')]
    public function adminEditArticleWeight(): void {}

    #[Path('staff/articles/delete-article')]
    #[Component(\TN\TN_CMS\Component\Article\Admin\ListArticles\DeleteArticle::class)]
    #[RoleOnly('article-editor')]
    public function adminDeleteArticle(): void {}

    #[Path('staff/articles/edit')]
    #[Component(\TN\TN_CMS\Component\Article\Admin\EditArticle\EditArticle::class)]
    #[RolesOnly(['article-editor', 'article-author'])]
    public function adminEditArticle(): void {}

    #[Path('staff/articles/edit/metadata')]
    #[Component(\TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleMetadataEditor\ArticleMetadataEditor::class)]
    #[RolesOnly(['article-editor', 'article-author'])]
    public function adminArticleMetadataEditor(): void {}

    #[Path('staff/articles/edit/url-stub')]
    #[Component(\TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleUrlStubEditor\ArticleUrlStubEditor::class)]
    #[RolesOnly(['article-editor', 'article-author'])]
    public function adminEditArticleArticleUrlStubEditor(): void {}

    #[Path('staff/articles/edit/thumbnail')]
    #[Component(\TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleThumbnailEditor\ArticleThumbnailEditor::class)]
    #[RolesOnly(['article-editor', 'article-author'])]
    public function adminEditArticleArticleThumbnailEditor(): void {}

    #[Path('staff/articles/edit/save')]
    #[Component(\TN\TN_CMS\Component\Article\Admin\EditArticle\SaveProperties::class)]
    #[RolesOnly(['article-editor', 'article-author'])]
    public function adminSaveArticleProperties(): void {}

    #[Path('staff/articles/edit/image/alt')]
    #[Component(\TN\TN_CMS\Component\Article\Admin\EditArticle\GetImageAlt::class)]
    #[RolesOnly(['article-editor', 'article-author'])]
    public function adminGetImageAlt(): void {}
}
