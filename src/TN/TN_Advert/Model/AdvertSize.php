<?php

namespace TN\TN_Advert\Model;

abstract class AdvertSize
{
    use \TN\TN_Core\Trait\Getter;
    use \TN\TN_Core\Trait\ExtendedSingletons;

    /** @var string non-db identifier */
    protected string $key;

    /** @var string non-db identifier */
    protected string $readable;
}