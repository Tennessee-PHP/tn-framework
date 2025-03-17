<?php

namespace TN\TN_CMS\Component\ListArticlesBox;

use TN\TN_CMS\Model\Article;
use TN\TN_Core\Component\Renderer\TemplateRender;

class ListArticlesBox extends \TN\TN_Core\Component\Component
{
    use TemplateRender;

    /** @var int */
    public int $numArticles = 10;

    /** @var Article[] */
    public array $articles = [];

    /**
     * @var array
     * @see Article::getArticles()
     */
    public array $filters = [];

    /** @var string */
    public string $orientation = '';

    /** @var string */
    public string $articlesHeader = '';

    /** @var string|null */
    public ?string $moreLink = null;

    /** @var string|null */
    public ?string $sortProperty = null;

    /** @var string|null */
    public ?string $sortDirection = null;

    /** @var bool */
    public bool $firstLarger = true;

    /** @var int  */
    public int $bonusArticles = 0;

    /** var string */
    protected string $template = 'TN/Component/CMS/ListArticlesBox/Element.tpl';


    /** constructor that accepts these arguments */

    public function __construct(array $filters, int $numArticles, string $orientation, string $articlesHeader, bool $firstLarger,
                                ?string $moreLink = null, ?string $sortProperty = null, ?string $sortDirection = null, int $bonusArticles = 0)
    {
        parent::__construct();

        $this->filters = $filters;
        $this->numArticles = $numArticles;
        $this->orientation = $orientation;
        $this->articlesHeader = $articlesHeader;
        $this->firstLarger = $firstLarger;
        $this->moreLink = $moreLink;
        $this->sortProperty = $sortProperty;
        $this->sortDirection = $sortDirection;
        $this->bonusArticles = $bonusArticles;

    }

    public function prepare(): void
    {
        $this->fetchArticles();
    }

    public function getArticle(): Article
    {
        return array_shift($this->articles);
    }

    protected function fetchArticles(): void
    {
        $this->filters = array_merge($this->filters, ['state' => Article::STATE_PUBLISHED, 'inPast' => true]);
        $this->articles = Article::getArticles(
            $this->filters, $this->sortProperty, $this->sortDirection, 0,  $this->numArticles + $this->bonusArticles
        );
    }

}