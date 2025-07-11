<?php

namespace TN\TN_CMS\Model;

use PDO;
use TN\TN_CMS\Model\Tag\Tag;
use TN\TN_CMS\Model\Tag\TaggedContent;
use TN\TN_Core\Attribute\Constraints\Strlen;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Component\Renderer\Page\Page;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Attribute\Cache as CacheAttribute;
use TN\TN_Core\Model\Storage\Cache as CacheStorage;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Strings\Strings;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;

/**
 * @property-read string $readableContentType
 * @property-read string $creatorName
 * @property-read string $creatorAvatar
 * @property-read string $finalThumbnailSrc
 * @property-read string $finalVThumbnailSrc
 */
#[TableName('cms_page_entries')]
#[CacheAttribute('v1.3')]
class PageEntry implements Persistence
{
    use MySQL;
    use PersistentModel;

    const string cacheIndexId = 'i';
    const string cacheIndexPrimary = 'p';
    const string cacheIndexMatchedTag = 't';
    const string cacheIndexMatchedTags = 's';
    const string cacheIndexMatchedWordsCount = 'w';
    const string cacheTimeFactor = 'tf';
    const string cacheSearchTitleFactor = 'sf';
    const string cacheSearchWordsFactor = 'wf';
    const string cacheFactor = 'f';

    public string $key = '';
    public string $path = '';
    public string $title = '';
    public string $subtitle = '';
    #[Strlen(0, 10000)] public string $description = '';
    public string $navigationParent = '';
    public string $originalTitle = '';
    public string $originalSubtitle = '';
    public string $originalDescription = '';
    public int $ts = 0;
    public bool $alwaysCurrent = false;
    public int $weight = 0;
    public bool $sitemap = false;
    public string $thumbnailSrc = '';
    public string $vThumbnailSrc = '';
    public string $contentId = '';
    public string $contentClass = '';
    public ?int $creatorId = null;
    public int $numTags = 0;
    #[Impersistent] public ?bool $primary = null;
    #[Impersistent] public ?string $matchedTag = null;
    #[Impersistent] public ?array $matchedTags = null;
    #[Impersistent] public ?int $matchedWordsCount = null;
    #[Impersistent] public ?float $factor = null;
    #[Impersistent] public ?float $timeFactor = null;
    #[Impersistent] public ?int $searchWordsFactor = null;
    #[Impersistent] public ?float $searchTitleFactor = null;
    #[Impersistent] public bool $updateFromContent = false;
    #[Impersistent] public int $searchSelectedCount = 0;
    #[Impersistent] public float $searchSelectedRate = 0.0;

    /**
     * @return string[]
     */
    public static function getAllContentClasses(): array
    {
        return array_merge([self::class], Content::getContentClasses());
    }

    /**
     * factory get one
     * @param int $id
     * @return PageEntry|null
     */
    public static function getPageEntry(int $id): ?PageEntry
    {
        return static::readFromId($id);
    }

    /**
     * factory get one for a content item
     * @param string $contentClass
     * @param string|int $contentId
     * @return PageEntry|null
     */
    public static function getPageEntryForContentItem(string $contentClass, string|int $contentId): ?PageEntry
    {
        $results = self::searchByProperties([
            'contentClass' => $contentClass,
            'contentId' => (string)$contentId
        ]);
        return empty($results) ? null : $results[0];
    }

    /**
     * get all ids of this content that have their own content class
     * @param string $contentClass
     * @return array
     */
    public static function getPageEntryContentIdsFromClass(string $contentClass): array
    {
        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $table = self::getTableName();
        $stmt = $db->prepare("SELECT contentId FROM {$table} WHERE `contentClass` = ?");
        $stmt->execute([$contentClass]);
        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ids[] = $row['contentId'];
        }
        return $ids;
    }

    /**
     * factory get multi - get all the objects stored on this class. Sorts by weight/time/other factors.
     * @param array $filters keys: title, search, tag (string), path, onlyNoTags, onlyNoThumbnail, contentClassExclude
     * @param int $start
     * @param int $num
     * @return array
     */
    public static function getPageEntriesResults(array $filters): array
    {
        // convert contentClassOnly to contentClassExclude
        if (isset($filters['contentClassOnly'])) {
            $filters['contentClassExclude'] = array_diff(self::getAllContentClasses(), $filters['contentClassOnly']);
            unset($filters['contentClassOnly']);
        }

        // if the first search with a tag doesn't work, do a subsequent one on titles
        $filters['search'] = trim($filters['search']);
        if (!empty($filters['search'])) {
            $pageEntries = [];
            $wordCount = 0;
            foreach (explode(' ', $filters['search']) as $word) {
                if (strlen($word) < 3) {
                    continue;
                }
                $wordCount += 1;
                $searchFilters = array_merge($filters, ['search' => $word]);
                foreach (self::getPageEntriesQuery($searchFilters) as $pageEntry) {
                    if (!isset($pageEntries[$pageEntry->id])) {
                        $pageEntries[$pageEntry->id] = $pageEntry;
                        $pageEntry->matchedTags = [];
                        $pageEntry->matchedWordsCount = 0;
                    }
                    $existingPageEntry = $pageEntries[$pageEntry->id];
                    $existingPageEntry->matchedTags[] = $pageEntry->matchedTag;
                    $existingPageEntry->matchedWordsCount += 1;
                    $existingPageEntry->primary = $existingPageEntry->primary && $pageEntry->primary;
                }
            }

            foreach ($pageEntries as $pageEntry) {
                if ($pageEntry->matchedWordsCount < $wordCount) {
                    $pageEntry->primary = false;
                }
            }

            // cast back to numerative array
            $pageEntries = array_values($pageEntries);

            // if we didn't get anything, try searching on titles
            if (empty($pageEntries)) {
                $pageEntries = self::getPageEntriesQuery([
                    'title' => $filters['search']
                ]);
            }
        } else {
            $pageEntries = self::getPageEntriesQuery($filters);
        }

        // apply the sorting here
        $factors = [];
        $primaries = [];
        foreach ($pageEntries as $pageEntry) {
            $pageEntry->setFactor(!empty($filters['search']) ? $filters['search'] : false);
            $factors[] = $pageEntry->factor;
            $primaries[] = $pageEntry->primary;
        }
        array_multisort($primaries, SORT_DESC, $factors, SORT_DESC, $pageEntries);

        // now let's extract the id, primary and matchedTags, matchedWordsCount
        $results = [];
        foreach ($pageEntries as $pageEntry) {
            $results[] = [
                self::cacheIndexId => $pageEntry->id,
                self::cacheIndexPrimary => $pageEntry->primary,
                self::cacheIndexMatchedTag => $pageEntry->matchedTag,
                self::cacheIndexMatchedTags => $pageEntry->matchedTags,
                self::cacheIndexMatchedWordsCount => $pageEntry->matchedWordsCount,
                self::cacheTimeFactor => $pageEntry->timeFactor,
                self::cacheSearchTitleFactor => $pageEntry->searchTitleFactor,
                self::cacheSearchWordsFactor => $pageEntry->searchWordsFactor,
                self::cacheFactor => $pageEntry->factor
            ];
        }
        return $results;
    }

    public static function getPageEntries(array $filters, int $start, int $num): array
    {
        $searchCacheIdentifier = md5(serialize($filters));
        $cacheKey = static::getCacheKey('search', $searchCacheIdentifier);
        $results = CacheStorage::get($cacheKey);
        if (!$results) {
            $results = self::getPageEntriesResults($filters);
            CacheStorage::set($cacheKey, $results, static::getCacheLifespan());
            CacheStorage::setAdd(static::getCacheKey('set', 'searches'), $cacheKey, static::getCacheLifespan());
        }

        // now slice the results
        $results = array_slice($results, $start, $num);

        // now instantiate each page entry (maybe again!) and return
        $pageEntries = [];
        foreach ($results as $result) {
            $pageEntry = static::readFromId($result[self::cacheIndexId]);
            if (!$pageEntry) {
                // Skip entries that no longer exist in the database
                continue;
            }
            $pageEntry->primary = $result[self::cacheIndexPrimary];
            $pageEntry->matchedTag = $result[self::cacheIndexMatchedTag];
            $pageEntry->matchedTags = $result[self::cacheIndexMatchedTags];
            $pageEntry->matchedWordsCount = $result[self::cacheIndexMatchedWordsCount];
            $pageEntry->timeFactor = $result[self::cacheTimeFactor];
            $pageEntry->searchTitleFactor = $result[self::cacheSearchTitleFactor];
            $pageEntry->searchWordsFactor = $result[self::cacheSearchWordsFactor];
            $pageEntry->factor = $result[self::cacheFactor];
            $pageEntries[] = $pageEntry;
        }
        return $pageEntries;
    }

    /**
     * @param array $filters
     * @return PageEntry[]
     * @todo: refactor with new search system, but it needs both support for grouping and property values taken from other tables
     */
    private static function getPageEntriesQuery(array $filters): array
    {
        $search = !empty($filters['search']) ? $filters['search'] : $filters['tag'];

        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $table = self::getTableName();
        $selects = ["p.*"];
        $tables = ["{$table} as p"];
        $params = [];
        $conditions = [];

        if (!empty($search)) {
            if (str_contains($search, '{|}')) {
                $orSearch = explode('{|}', $search);
            } else if (str_contains($search, '{+}')) {
                $andSearch = explode('{+}', $search, 2);
            }

            if ($andSearch) {
                $taggedContentTable = TaggedContent::getTableName();
                $tagsTable = Tag::getTableName();
                $selects[] = "c.`primary`";
                $selects[] = "t.`text` as matchedTag";
                $tables[] = "{$taggedContentTable} as c";
                $tables[] = "{$tagsTable} as t";
                $conditions[] = "t.`text` LIKE ?";
                $params[] = $andSearch[0];
                $conditions[] = "t.id = c.tagId";
                $conditions[] = "c.contentClass = p.contentClass";
                $conditions[] = "c.contentId = p.contentId";
                $tables[] = "{$taggedContentTable} as c2";
                $tables[] = "{$tagsTable} as t2";
                $conditions[] = "t2.`text` LIKE ?";
                $params[] = $andSearch[1];
                $conditions[] = "t2.id = c2.tagId";
                $conditions[] = "c2.contentClass = p.contentClass";
                $conditions[] = "c2.contentId = p.contentId";
            } else {
                $taggedContentTable = TaggedContent::getTableName();
                $tagsTable = Tag::getTableName();
                $selects[] = "c.`primary`";
                $selects[] = "t.`text` as matchedTag";
                $tables[] = "{$taggedContentTable} as c";
                $tables[] = "{$tagsTable} as t";
                $exact = empty($filters['search']);

                $searchParam = '';
                if (!$exact) {
                    $searchParam .= '%';
                }
                $searchParam .= $search;
                if (!$exact) {
                    $searchParam .= '%';
                }

                if ($orSearch) {
                    $orCondition = '(';
                    $orConditions = [];
                    foreach ($orSearch as $orTag) {
                        $orConditions[] = "t.`text` LIKE ?";
                        $params[] = $orTag;
                    }
                    $orCondition .= implode(' OR ', $orConditions);
                    $orCondition .= ')';
                    $conditions[] = $orCondition;
                } else {
                    $conditions[] = "t.`text` LIKE ?";
                    $params[] = $searchParam;
                }

                $conditions[] = "t.id = c.tagId";
                $conditions[] = "c.contentClass = p.contentClass";
                $conditions[] = "c.contentId = p.contentId";
            }
        }

        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue;
            }
            switch ($key) {
                case 'path':

                case 'title':
                    $conditions[] = "p.`{$key}` LIKE ?";
                    $params[] = "%{$value}%";
                    break;
                case 'creatorId':
                    $conditions[] = "p.`creatorId` = ?";
                    $params[] = $value;
                    break;
                case 'excludeId':
                    $conditions[] = "p.`id` != ?";
                    $params[] = $value;
                    break;
                case 'onlyNoTags':
                    $conditions[] = 'p.`numTags` = ?';
                    $params[] = 0;
                    break;
                case 'onlyNoThumbnail':
                    $conditions[] = 'p.`thumbnailSrc` = ?';
                    $params[] = '';
                    break;
                case 'contentClassExclude':
                    $placeHolders = implode(',', array_fill(0, count($value), '?'));
                    $conditions[] = "p.`contentClass` NOT IN ({$placeHolders})";
                    foreach ($value as $class) {
                        $params[] = $class;
                    }
                    break;
            }
        }

        // convert everything to strings
        $tables = implode(", ", $tables);
        $selects = implode(", ", $selects);
        $conditions = empty($conditions) ? '' : ('WHERE ' . implode(" AND ", $conditions));
        $query = "
            SELECT DISTINCT {$selects}
            FROM {$tables}
            {$conditions}
            ORDER BY p.alwaysCurrent DESC, p.ts DESC, p.weight DESC
            LIMIT 0, 1000
            ";

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        $objects = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $result) {
            $objects[] = static::getInstance($result);
        }
        return $objects;
    }

    /**
     * @param int $ts
     * @return float
     */
    private static function calculateTimeFactor(int $ts): float
    {
        $diff = Time::getNow() - $ts;
        $tsScale = [
            Time::ONE_YEAR * 10, // 0
            Time::ONE_YEAR * 2, // 1
            Time::ONE_YEAR, // 2
            Time::ONE_YEAR * 0.25, // 3
            Time::ONE_WEEK * 4, // 4
            Time::ONE_WEEK, // 5
            Time::ONE_DAY * 3, // 6
            Time::ONE_DAY, // 7
            Time::ONE_HOUR * 5, // 8
            Time::ONE_HOUR, // 9
            0, // 10
        ];
        $i = 0;
        while ($diff < $tsScale[$i]) {
            $i += 1;
        }
        if (in_array($i, [0, 10])) {
            $timeFactor = $i;
        } else {
            $timeFactor = $i + (
                ($diff - $tsScale[$i - 1]) / ($tsScale[$i] - $tsScale[$i - 1])
            );
        }
        return $timeFactor;
    }

    private static function calculateSearchWordsFactor(?int $matchedWordsCount): float
    {
        return pow(10, $matchedWordsCount);
    }

    private static function calculateSearchTitleFactor(string $title, string $search): float
    {
        $percent = 0;
        similar_text($search, $title, $percent);
        return $percent;
    }

    /**
     * @param int $weight
     * @param int $ts
     * @return float
     */
    public static function calculateFactorExternal(int $weight, int $ts): float
    {
        // got to see how old the article is
        $timeFactor = self::calculateTimeFactor($ts);
        $weightFactor = $weight;
        return $timeFactor + $weightFactor;
    }

    /**
     * @param array $keys
     * @param int $ts
     * @return void
     * @throws ValidationException
     */
    public static function updateTimestampsFromKeys(array $keys, int $ts = 0): void
    {
        // if ts is zero set it to current time
        if ($ts === 0) {
            $ts = Time::getNow();
        }
        foreach ($keys as $key) {
            $pageEntry = self::searchByProperty('key', $key);
            $pageEntry = count($pageEntry) ? $pageEntry[0] : null;
            if (!$pageEntry) {
                continue;
            }
            $pageEntry->update([
                'ts' => $ts,
                'alwaysCurrent' => false
            ]);
        }
    }

    /**
     * construct a page entry.
     * @param Page $page
     * @param string $key
     * @param string $path
     * @param bool $sitemap
     * @return PageEntry
     * @throws ValidationException
     */
    public static function addFromPage(Page $page, string $key, string $path, bool $sitemap = true): PageEntry
    {
        // let's try to read this key
        $updates = [];
        $pageEntry = self::searchByProperty('key', $key);
        $pageEntry = count($pageEntry) ? $pageEntry[0] : null;

        // none? let's create a new one
        if (!$pageEntry) {
            $pageEntry = self::getInstance();
            $updates['key'] = $key;
            $updates['path'] = $path;
            $updates['sitemap'] = $sitemap;
            $updates['ts'] = 0;
            $updates['alwaysCurrent'] = true;
            $updates['contentClass'] = self::class;
        }

        foreach (['title', 'subtitle', 'description'] as $prop) {
            // set original values if they're different
            $oProp = 'original' . ucfirst($prop);
            if ($pageEntry->$oProp !== $page->$prop && $page->$prop) {
                $updates[$oProp] = $page->$prop;
            }

            if ($page->openGraphImage) {
                $updates['thumbnailSrc'] = $page->openGraphImage;
                $updates['vThumbnailSrc'] = $page->openGraphImage;
            }

            // set values if none
            if (empty($pageEntry->$prop) && $page->$prop) {
                $updates[$prop] = $page->$prop;
            }

            if ($pageEntry->$oProp === $pageEntry->$prop && $pageEntry->$prop !== $page->$prop && $page->$prop) {
                // the attribute was never changed, but now has, from the code. update it!
                $updates[$prop] = $page->$prop;
                $updates[$oProp] = $page->$prop;
            } else if (!empty($pageEntry->$prop)) {
                // set the updated values on the page
                $page->$prop = $pageEntry->$prop;
            }
        }

        foreach ($updates as $key => &$value) {
            if (is_string($value)) {
                $value = trim($value);
                $value = Strings::removeEmojis($value);
            }
        }

        // @todo add the keywords to the page

        if (!empty($updates)) {
            $pageEntry->update($updates);
        }

        return $pageEntry;
    }

    public static function getReadableContentType(): string
    {
        return 'Page';
    }

    /**
     * @param string $name
     * @return mixed
     * magic getter should return values for all the properties on the article
     */
    public function __get(string $name): mixed
    {
        $val = match ($name) {
            'readableContentType' => $this->getReadableContentTypeFromClass(),
            'creatorName' => $this->getCreatorName(),
            'creatorAvatar' => $this->getCreatorAvatar(),
            'finalThumbnailSrc' => !empty($this->thumbnailSrc) ? $this->thumbnailSrc : $_ENV['IMG_BASE_URL'] . 'content-landing-pages/defaultthumbnail.png',
            'finalVThumbnailSrc' => !empty($this->vThumbnailSrc) ? $this->vThumbnailSrc : $_ENV['IMG_BASE_URL'] . 'content-landing-pages/defaultthumbnail.png',
            default => (property_exists($this, $name) && isset($this->$name)) ? $this->$name : null
        };

        return $val;
    }

    /**
     * @return string|null
     */
    protected function getCreatorName(): ?string
    {
        if (!$this->creatorId) {
            return null;
        }
        $user = User::readFromId($this->creatorId);
        return $user->name;
    }

    /**
     * @return string|null
     */
    protected function getCreatorAvatar(): ?string
    {
        if (!$this->creatorId) {
            return null;
        }
        // Basic implementation - use a default avatar
        return $_ENV['IMG_BASE_URL'] . 'staffer-bio-images/default.png';
    }

    /**
     * @return string
     */
    protected function getReadableContentTypeFromClass(): string
    {
        $class = $this->getContentClass();
        if (empty($class)) {
            return '';
        }
        return $class::getReadableContentType();
    }

    /**
     * @return int a timestamp for the age of this content, considering both alwaysCurrent and ts
     */
    public function getTimestamp(): int
    {
        return $this->alwaysCurrent ? Time::getNow() : $this->ts;
    }

    /**
     * @param string|false $search
     * @return void
     */
    protected function setFactor(string|false $search): void
    {
        $this->timeFactor = self::calculateTimeFactor($this->getTimestamp());
        if ($search) {
            $this->searchTitleFactor = self::calculateSearchTitleFactor($this->title, $search);
            $this->searchWordsFactor = self::calculateSearchWordsFactor($this->matchedWordsCount);
        } else {
            $this->searchTitleFactor = 0;
            $this->searchWordsFactor = 0;
        }
        $this->factor = $this->timeFactor + $this->weight + $this->searchTitleFactor + $this->searchWordsFactor;
    }

    /**
     * after save insert
     */
    protected function afterSaveInsert(): void
    {
        // after creation, page entries need to be updated so their contentId matches their id
        if ($this->getContentClass() === self::class) {
            $this->update(['contentId' => $this->id]);
        }
    }

    /**
     * @param array $changedProperties
     * @return void
     */
    protected function afterSaveUpdate(array $changedProperties): void
    {
        if ($this->updateFromContent) {
            return;
        }
        $class = $this->getContentClass();
        if ($class === get_class($this) || empty($class)) {
            return;
        }
        $item = $class::readFromId($this->contentId);
        if (!$item) {
            return;
        }
        $item->updateFromPageEntry($this);
    }

    /**
     * @return void
     * @throws ValidationException
     */
    public function updateNumTags(): void
    {
        $this->update([
            'numTags' => count(TaggedContent::getFromContentItem($this->getContentClass(), $this->contentId))
        ]);
    }

    /**
     * @return mixed
     */
    public function getContent(): mixed
    {
        if ($this->getContentClass() === self::class) {
            return $this;
        } else {
            return Stack::resolveClassName($this->getContentClass())::getContentItem($this->contentId);
        }
    }

    /**
     * @param string $contentClass
     * @return string
     */
    public static function getUpdatedContentClass(string $contentClass): string
    {
        return $contentClass;
    }

    public function getContentClass(): string
    {
        return self::getUpdatedContentClass($this->contentClass);
    }
}
