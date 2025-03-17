<?php

namespace TN\TN_CMS\Model;

/**
 * page entries for a given site map group (by navigation parent)
 * 
 */
class SiteMapGroup
{
    /** @var string navigation parent of this group */
    public string $navigationParent = '';

    /** @var array the page entries */
    public array $pageEntries = [];

    /**
     * constructor
     * @param string $navigationParent
     */
    public function __construct(string $navigationParent = '') {
        $this->navigationParent = $navigationParent;
    }
}