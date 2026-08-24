<?php

namespace TN\TN_CMS\Model;

use TN\TN_Core\Model\Storage\Cache;
use TN\TN_Core\Model\Storage\NodeShmCache;

/**
 * Versioned Redis + /dev/shm keys for homepage / landing reservoir listings.
 */
class PageEntryListingCache
{
    public const string VERSION_KEY = 'page-entry-listing:ver';
    public const string KEY_PREFIX = 'page-entry-listing:';
    public const int TTL = 3600;

    public static function key(
        ?string $tag,
        int $num,
        bool $featured,
        ?array $contentClasses,
        ?int $excludePageEntryId
    ): string {
        return self::keyForVersion(
            Cache::generation(self::VERSION_KEY),
            $tag,
            $num,
            $featured,
            $contentClasses,
            $excludePageEntryId
        );
    }

    public static function keyForVersion(
        int $ver,
        ?string $tag,
        int $num,
        bool $featured,
        ?array $contentClasses,
        ?int $excludePageEntryId
    ): string {
        $classes = $contentClasses ?? [];
        sort($classes);
        $payload = json_encode([
            'tag' => $tag,
            'num' => $num,
            'featured' => $featured,
            'contentClasses' => array_values($classes),
            'exclude' => $excludePageEntryId,
        ], JSON_UNESCAPED_SLASHES);
        return self::KEY_PREFIX . $ver . ':' . hash('sha256', (string) $payload);
    }

    public static function bump(): void
    {
        Cache::bumpGeneration(self::VERSION_KEY);
        NodeShmCache::deletePrefix(self::KEY_PREFIX);
    }
}
