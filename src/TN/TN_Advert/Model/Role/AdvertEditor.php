<?php

namespace TN\TN_Advert\Model\Role;

use TN\TN_Core\Model\Role\Role;

class AdvertEditor extends Role {
    public string $key = 'advert-editor';
    public string $readable = 'Advert Editor';
    public string $description = 'Can edit adverts that appear on the website and in articles';
    public ?string $roleGroup = 'marketing-team';
}