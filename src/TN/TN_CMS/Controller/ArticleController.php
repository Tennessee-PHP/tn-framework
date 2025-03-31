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
    #[RoleOnly('backend-article-list-viewer')]
    #[RoleOnly('article-editor')]
    #[RoleOnly('article-author')]
    public function adminListArticles(): void {}

    #[Path('staff/articles/weight')]
    #[Component(\TN\TN_CMS\Component\Article\Admin\ListArticles\EditArticleWeight::class)]
    #[RoleOnly('article-editor')]
    public function adminEditArticleWeight(): void {}

    #[Path('staff/articles/delete')]
    #[Component(\TN\TN_CMS\Component\Article\Admin\ListArticles\DeleteArticle::class)]
    #[RoleOnly('article-editor')]
    public function adminDeleteArticle(): void {}

    #[Path('staff/articles/edit')]
    #[Component(\TN\TN_CMS\Component\Article\Admin\EditArticle\EditArticle::class)]
    #[RoleOnly('article-editor')]
    #[RoleOnly('article-author')]
    public function adminEditArticle(): void {}

    #[Path('staff/articles/edit/save')]
    #[Component(\TN\TN_CMS\Component\Article\Admin\EditArticle\SaveProperties::class)]
    #[RoleOnly('article-editor')]
    #[RoleOnly('article-author')]
    public function adminSaveArticleProperties(): void {}

    #[Path('staff/articles/edit/image/alt')]
    #[Component(\TN\TN_CMS\Component\Article\Admin\EditArticle\GetImageAlt::class)]
    #[RoleOnly('article-editor')]
    #[RoleOnly('article-author')]
    public function adminGetImageAlt(): void {}
}
