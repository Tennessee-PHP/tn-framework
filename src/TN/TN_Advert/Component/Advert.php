<?php

namespace TN\TN_Advert\Component;

use TN\TN_Advert\Model\Advert as AdvertModel;
use TN\TN_Core\Component\Component;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Model\User\User;

class Advert extends HTMLComponent {
    public ?string $advertSpotKey = null;
    public ?AdvertModel $advert;

    public function prepare(): void
    {
        $this->advert = AdvertModel::getAdvertToShow(User::getActive(), $this->advertSpotKey);
    }
}