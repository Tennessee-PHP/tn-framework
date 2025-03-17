<?php

namespace TN\TN_Advert\Model;

abstract class AdvertSpot
{
    use \TN\TN_Core\Trait\Getter;
    use \TN\TN_Core\Trait\ExtendedSingletons;

    /** @var string non-db identifier */
    protected string $key;

    /** @var string the name of the advert location */
    protected string $name;

    /** @var string text description of advert location */
    protected string $description;

    /** @var string banner/text/site message/etc */
    protected string $sizeType;

}