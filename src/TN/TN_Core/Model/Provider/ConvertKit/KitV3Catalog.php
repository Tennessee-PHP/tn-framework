<?php

namespace TN\TN_Core\Model\Provider\ConvertKit;

use TN\TN_Core\Model\Storage\Cache;
use TN\TN_Core\Model\Time\Time;

/**
 * Cached Kit (ConvertKit) v3 catalog lookups for forms and tags.
 */
class KitV3Catalog
{
    private const CACHE_TTL = Time::ONE_HOUR;

    private const CACHE_KEY_FORMS = 'convertkit:v3:form-ids';

    private const CACHE_KEY_TAGS = 'convertkit:v3:tag-name-to-id';

    /** @return int[] */
    public static function getAllowedFormIds(): array
    {
        $cached = Cache::get(self::CACHE_KEY_FORMS);
        if (is_array($cached)) {
            return $cached;
        }

        $api = new \ConvertKit_API\ConvertKit_API($_ENV['CONVERTKIT_KEY'], $_ENV['CONVERTKIT_SECRET']);
        $response = $api->make_request('v3/forms', 'GET', ['api_key' => $_ENV['CONVERTKIT_KEY']]);
        $forms = is_object($response) && isset($response->forms) && is_array($response->forms)
            ? $response->forms
            : [];

        $ids = [];
        foreach ($forms as $form) {
            if (isset($form->id) && is_numeric((string) $form->id)) {
                $ids[] = (int) $form->id;
            }
        }

        Cache::set(self::CACHE_KEY_FORMS, $ids, self::CACHE_TTL);
        return $ids;
    }

    /** @return array<string, int> lowercase tag name => id */
    public static function getTagNameToIdMap(): array
    {
        $cached = Cache::get(self::CACHE_KEY_TAGS);
        if (is_array($cached)) {
            return $cached;
        }

        $api = new \ConvertKit_API\ConvertKit_API($_ENV['CONVERTKIT_KEY'], $_ENV['CONVERTKIT_SECRET']);
        $response = $api->make_request('v3/tags', 'GET', ['api_key' => $_ENV['CONVERTKIT_KEY']]);
        $tags = is_object($response) && isset($response->tags) && is_array($response->tags)
            ? $response->tags
            : [];

        $map = [];
        foreach ($tags as $tag) {
            if (!isset($tag->id, $tag->name)) {
                continue;
            }
            $label = mb_strtolower(trim((string) $tag->name), 'UTF-8');
            if ($label === '') {
                continue;
            }
            $map[$label] = (int) $tag->id;
        }

        Cache::set(self::CACHE_KEY_TAGS, $map, self::CACHE_TTL);
        return $map;
    }

    public static function resolveTagIdByName(string $name): ?int
    {
        $label = mb_strtolower(trim($name), 'UTF-8');
        if ($label === '') {
            return null;
        }
        $map = self::getTagNameToIdMap();
        return $map[$label] ?? null;
    }
}
