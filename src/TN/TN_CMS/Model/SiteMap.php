<?php

namespace TN\TN_CMS\Model;

/**
 * a site map, all PageEntry instances organised into navigationParents
 *
 */
class SiteMap
{
    /** @var array the site map groups */
    public array $siteMapGroups = [];

    /**
     * gets a site map!
     */
    public static function getSiteMap(): SiteMap
    {
        $siteMap = new SiteMap();
        foreach (PageEntry::readAll() as $pageEntry) {
            if (!isset($siteMapGroups[$pageEntry->navigationParent])) {
                $siteMap->siteMapGroups[$pageEntry->navigationParent] = new SiteMapGroup($pageEntry->navigationParent);
            }
            $siteMap->siteMapGroups[$pageEntry->navigationParent]->pageEntries[] = $pageEntry;
        }
        return $siteMap;
    }

}