<?php

namespace TN\TN_CMS\Component\Article\ListArticles;

use TN\TN_CMS\Component\Input\Select\CategorySelect;
use TN\TN_CMS\Model\Article;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Component\User\Select\UserSelect\UserSelect;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\User\User;
use \TN\TN_Core\Attribute\Components\Route;

#[Page('Articles', 'articles', false)]
#[Route('TN_CMS:Article:listArticles')]
#[Reloadable]
class ListArticles extends HTMLComponent
{
    public ?string $tag = null;
    public UserSelect $userSelect;
    public CategorySelect $categorySelect;
    public Pagination $pagination;
    public array $articles;

    public function prepare(): void
    {
        $this->userSelect = new UserSelect([
            'users' => User::getUsersWithRole('article-author'),
            'allLabel' => 'All Authors',
            'displayProperty' => 'name'
        ]);
        $this->userSelect->prepare();
        $this->categorySelect = new CategorySelect();
        $this->categorySelect->prepare();
        $search = new SearchArguments();

        $filters = [];
        $filters['state'] = Article::STATE_PUBLISHED;
        $filters['inPast'] = true;

        if ($this->tag) {
            $filters['tag'] = $this->tag;
        } else {
            if ($this->userSelect->selected) {
                $filters['authorId'] = $this->userSelect->selected->id;
            }
            if ($this->categorySelect->selected) {
                $filters['category'] = $this->categorySelect->selected->object->tagText;
            }
        }



        $count = Article::getArticles($filters, null, null, 0, 100, true);
        $this->pagination = new Pagination([
            'itemCount' => $count,
            'itemsPerPage' => 99,
            'search' => $search
        ]);
        $this->pagination->prepare();
        $this->articles = Article::getArticles($filters, null, null, $this->pagination->queryStart, $this->pagination->itemsPerPage);
    }
}