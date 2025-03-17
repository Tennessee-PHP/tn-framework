<?php

namespace TN\TN_CMS\Attribute\Route;

use TN\TN_CMS\Model\LandingPage;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Request\Request;

#[\Attribute(\Attribute::TARGET_METHOD)]
class LandingPagePath extends Path {
    public function __construct(
        public string $path = '',
        public ?string $method = null
    )
    {
        parent::__construct($path, $method);
    }

    public function matches(Request $origin): bool
    {
        if (!($origin instanceof HTTPRequest)) {
            return false;
        }
        $request = $origin;
        if ($this->method && $this->method !== $request->method) {
            return false;
        }
        return (bool)LandingPage::searchOne(new SearchArguments([
            new SearchComparison('`urlStub`', 'LIKE', trim($request->path, '/')),
            new SearchComparison('`state`', '=', LandingPage::STATE_PUBLISHED)
        ]));
    }
}