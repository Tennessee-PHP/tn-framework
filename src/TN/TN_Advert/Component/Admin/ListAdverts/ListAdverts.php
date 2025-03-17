<?php

namespace TN\TN_Advert\Component\Admin\ListAdverts;

use TN\TN_Advert\Model\Advert;
use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use \TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorterDirection;

#[Page('Edit Adverts', 'View all the adverts', false)]
#[Route('TN_Advert:Admin:listAdverts')]
#[Reloadable]
class ListAdverts extends HTMLComponent
{
    /** @var Advert[] */ public array $adverts;
    public Advert $advert;
    #[FromQuery] public ?int $deleteId = null;
    #[FromQuery] public ?string $title = null;
    public Pagination $pagination;

    public function prepare(): void
    {
        if ($this->deleteId) {
            $advert = Advert::readFromId($this->deleteId);
            if ($advert instanceof Advert) {
                $advert->erase();
            }
        }

        $search = new SearchArguments(
            conditions: !empty($this->title) ? new SearchComparison('`title`', 'LIKE', "%{$this->title}%") : [],
            sorters: new SearchSorter('title', SearchSorterDirection::DESC )
        );

        $count = Advert::count($search);
        $this->pagination = new Pagination([
            'itemCount' => $count,
            'itemsPerPage' => 20,
            'search' => $search
        ]);
        $this->pagination->prepare();
        $this->adverts = Advert::search($search);

    }
}