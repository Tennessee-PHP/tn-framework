<?php

namespace TN\TN_Core\Attribute\Route;

use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Request\Request;

class RequestMatcher extends Matcher
{
    /**
     * constructor
     * @param string $path
     * @param string|null $method
     */
    public function __construct(
        public string $path,
        public ?string $method = null
    ) {}

    /**
     * get rules for this path - each path gets translated into > 1 regex to match against the request, e.g. with and
     * without dashes
     * @return string[]
     */
    protected function getRules(): array
    {
        $rules = [$this->path];
        if (strpos($this->path, '-')) {
            $rules[] = str_replace('-', '', $this->path);
        }
        return $rules;
    }

    /**
     * attempts to match this path against the current request address
     * @param Request $origin
     * @return bool
     */
    public function matches(Request $origin): bool
    {
        if (!($origin instanceof HTTPRequest)) {
            return false;
        }
        $request = $origin;
        if ($this->method && $this->method !== $request->method) {
            return false;
        }
        $rules = $this->getRules();
        foreach ($rules as $rule) {
            if (preg_match($this->getRegexFromRule($rule), $request->path)) {
                return true;
            }
        }
        return false;
    }

    /**
     * gets a regular expression for a route's rule, to try to match against the requested path
     * @param string $rule
     * @return string
     */
    private function getRegexFromRule(string $rule): string
    {
        // escape all regular expression stuff
        $rule = preg_quote($rule, '/');

        // recreate a regular expression for the now quoted rule argument e.g. "/:arg/"
        $rule = preg_replace("/\\\\:[^\\/\\\\]+/i", '[^\\/]+', $rule);

        // handle wildcards
        $rule = str_replace("\*", '.*', $rule);

        // now add the forward slashes and make sure it starts this way
        return '/^\/?' . $rule . '$/i';
    }
}
