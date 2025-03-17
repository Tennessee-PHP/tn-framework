<?php

namespace TN\TN_CMS\Component\Sitemap;

use TN\TN_CMS\Model\PageEntry;
use TN\TN_Core\Component\Renderer\XML\XML;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchLimit;
use TN\TN_Core\Model\PersistentModel\Search\SearchLogical;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\Time\Time;

class Sitemap extends XML {
    public function prepare(): void
    {
        $urls = [];

        foreach (PageEntry::search(new SearchArguments(
            conditions: new SearchLogical('OR', [
                new SearchComparison('`alwaysCurrent`', '=', 1),
                new SearchComparison('`ts`', '>=', Time::getNow() - Time::ONE_YEAR)
            ]),
            sorters: new SearchSorter('weight', 'DESC'),
            limit: new SearchLimit(0, 1000)
            )
        ) as $pageEntry) {
            $urls[] = [
                'loc' => $_ENV['BASE_URL'] . $pageEntry->path,
                'lastmod' => date('Y-m-d', $pageEntry->alwaysCurrent ? Time::getNow() : $pageEntry->ts),
                'changefreq' => $pageEntry->alwaysCurrent ? 'always' : 'weekly',
                'priority' => $pageEntry->weight / 10
            ];
        }

        $this->data = [
            'urlset' => [
                '_attributes' => [
                    'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9'
                ],
                'url' => $urls
            ]
        ];
    }
}