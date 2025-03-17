<?php

namespace TN\TN_Advert\Component\Admin\EditAdvert;

use TN\TN_Advert\Component\Admin\ListAdverts\ListAdverts;
use TN\TN_Advert\Model\Advert;
use TN\TN_Advert\Model\AdvertPlacement;
use TN\TN_Core\Attribute\Components\HTMLComponent\RequiresTinyMCE;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use \TN\TN_Core\Attribute\Components\Route;

#[Page('Edit Advert', 'Add or edit an advert', false)]
#[Route('TN_Advert:Admin:editAdvert')]
#[Reloadable]
#[Breadcrumb(ListAdverts::class)]
#[RequiresTinyMCE]
class EditAdvert extends HTMLComponent
{
    public string|int $id;
    public ?Advert $advert;
    public array $audienceOptions;
    public array $advertPlacements;
    public array $frequencyOptions;
    public array $groupedOptions;
    public array $advertStats;

    public function prepare(): void
    {
        $this->advert = Advert::readFromId($this->id);
        $this->audienceOptions = Advert::getAudienceOptions();
        $this->advertPlacements = AdvertPlacement::searchByProperty('advertId', $this->advert->id);
        $this->frequencyOptions = Advert::getAllFrequencies();
        $locationOptions = Advert::getAdvertSpotOptions();
        $this->groupedOptions = [];
        foreach ($locationOptions as $key => $option) {
            $sizeType = $option['sizeType'];

            if (!isset($this->groupedOptions[$sizeType])) {
                $this->groupedOptions[$sizeType] = [];
            }
            $option['key'] = $key;
            $this->groupedOptions[$sizeType][] = $option;
        }
    }
}