<?php

namespace TN\TN_Core\Model\Provider\ConvertKit;

use TN\TN_Core\Model\Storage\Cache;

/**
 * Cached Kit (ConvertKit) v3 catalog data: forms and tags for validation and tag-id resolution.
 * Uses {@see Cache} (Redis) with a one-hour TTL, matching list endpoints that use the public API key.
 */
class KitV3Catalog
{
    private const CACHE_TTL_SECONDS = 3600;

    private const FORMS_CACHE_KEY = 'kit_v3_insider_forms_payload';

    private const TAGS_CACHE_KEY = 'kit_v3_insider_tags_payload';

    /**
     * @return list<int> numeric form ids returned by GET /v3/forms
     */
    public static function getAllowedFormIds(): array
    {
        $data = self::getFormsPayload();
        if ($data === null) {
            return [];
        }
        $ids = [];
        foreach ($data['forms'] ?? [] as $form) {
            if (isset($form['id'])) {
                $ids[] = (int) $form['id'];
            }
        }
        return $ids;
    }

    /**
     * Map of lowercase trimmed tag name → numeric tag id (GET /v3/tags).
     *
     * @return array<string, int>
     */
    public static function getTagNameToIdMap(): array
    {
        $data = self::getTagsPayload();
        if ($data === null) {
            return [];
        }
        $map = [];
        foreach ($data['tags'] ?? [] as $tag) {
            if (!isset($tag['id'], $tag['name'])) {
                continue;
            }
            $key = mb_strtolower(trim((string) $tag['name']), 'UTF-8');
            if ($key !== '') {
                $map[$key] = (int) $tag['id'];
            }
        }
        return $map;
    }

    /**
     * @return array{forms?: list<array<string, mixed>>}|null
     */
    private static function getFormsPayload(): ?array
    {
        $cached = Cache::get(self::FORMS_CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }
        $key = (string) ($_ENV['CONVERTKIT_KEY'] ?? '');
        if ($key === '') {
            return null;
        }
        $url = 'https://api.convertkit.com/v3/forms?api_key=' . urlencode($key);
        $decoded = self::httpGetJson($url);
        if ($decoded === null) {
            return null;
        }
        Cache::set(self::FORMS_CACHE_KEY, $decoded, self::CACHE_TTL_SECONDS);
        return $decoded;
    }

    /**
     * @return array{tags?: list<array<string, mixed>>}|null
     */
    private static function getTagsPayload(): ?array
    {
        $cached = Cache::get(self::TAGS_CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }
        $key = (string) ($_ENV['CONVERTKIT_KEY'] ?? '');
        if ($key === '') {
            return null;
        }
        $url = 'https://api.convertkit.com/v3/tags?api_key=' . urlencode($key);
        $decoded = self::httpGetJson($url);
        if ($decoded === null) {
            return null;
        }
        Cache::set(self::TAGS_CACHE_KEY, $decoded, self::CACHE_TTL_SECONDS);
        return $decoded;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function httpGetJson(string $url): ?array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $code < 200 || $code >= 300) {
            error_log('KitV3Catalog: GET failed code=' . $code . ' url=' . $url);
            return null;
        }
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
