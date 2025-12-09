<?php

namespace TN\TN_CMS\Model;

use PDO;
use TN\TN_CMS\Model\Tag\Tag;
use TN\TN_CMS\Model\Tag\TaggedContent;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\AutoIncrement;
use TN\TN_Core\Attribute\MySQL\PrimaryKey;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\Validation;
use TN\TN_Core\Model\Storage\Cache;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Time\Time;

// reference can be removed post-migration

/**
 * $article->author
 * a single article in a content management system
 *
 */
#[TableName('cms_landing_pages')]
class LandingPage extends Content implements Persistence
{
    use MySQL;
    use PersistentModel;

    const int STATE_DRAFT = 1;
    const int STATE_PUBLISHED = 3;



    /**
     * @return array
     */
    public static function getAllStates(): array
    {
        return [
            self::STATE_DRAFT => 'Draft',
            self::STATE_PUBLISHED => 'Published'
        ];
    }

    /** @var string title of article */
    public string $title = '';

    /**  @var string description of article content */
    public string $description = '';

    /** @var string type of header */
    public string $headerType = '';

    /** @var string url of image */
    public string $thumbnailSrc = '';

    /** @var string page contents (html) */
    public string $content = '';

    /** @var string url stub of article */
    public string $urlStub = '';

    /** @var string level of content access required to view article */
    public string $contentRequired = '';

    /** @var int unix timestamp of page publish date */
    public int $publishedTs = 0;

    /** @var int current state of page */
    public int $state = self::STATE_DRAFT;

    /** @var int weight for sorting page */
    public int $weight = 5;

    /** @var int week of season associated with page */
    public int $week = 0;

    /** @var int year of season associated with page */
    public int $year = 0;

    /** @var bool for a non-logged in user, who came from a different site, should the site navigation be removed? */
    public bool $allowRemovedNavigation = false;

    /** @var string convertkit tag to send through to their API */
    public string $convertKitTag = '';

    /** @var int campaign ID if this is a sales page for landing */
    public int $campaignId = 0;

    /** @var int number of tags associated with this landing page */
    #[Impersistent]
    public int $numTags = 0;

    /** @var bool flag to track if update is from page entry */
    #[Impersistent]
    protected bool $updateFromPageEntry = false;

    #[Impersistent]
    protected array $processedDisplayParts = [];

    /**
     * loads up an object from mysql, given its id
     * @param string $urlStub
     * @return Article|null
     */
    public static function getPublishedMatchingUrlStub(string $urlStub): ?LandingPage
    {
        return static::searchOne(new SearchArguments([
            new SearchComparison('`urlStub`', 'LIKE', $urlStub),
            new SearchComparison('`state`', '=', self::STATE_PUBLISHED)
        ]));
    }

    /**
     * get all the objects stored on this class
     * @param array $filters : an array of filters. Supported keys are state, tag
     * @param string|null $sortProperty
     * @param int|null $sortDirection
     * @param int $start
     * @param int $num
     * @return array
     */
    public static function getLandingPages(
        array $filters = [],
        ?string $sortProperty = null,
        ?int $sortDirection = null,
        int   $start = 0,
        int $num = 100
    ): array {
        if (!$sortDirection) {
            $sortDirection = SORT_DESC;
        }

        $state = $filters['state'] ?? null;
        $tag = $filters['tag'] ?? null;

        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $table = self::getTableName();

        $values = [];
        $wheres = [];
        if ($tag) {
            $tagsTable = Tag::getTableName();
            $taggedContentsTable = TaggedContent::getTableName();
            $query = "SELECT DISTINCT a.`id`, a.`publishedTs`, a.`weight` FROM `{$table}` as a, {$tagsTable} as t, {$taggedContentsTable} as c";
            $wheres[] = "t.`text` LIKE ?";
            $values[] = $tag;
            $wheres[] = "t.`id` = c.`tagId`";
            $wheres[] = "c.`contentClass` = ?";
            $values[] = get_called_class();
            $wheres[] = "c.`contentId` = `a`.id";
        } else {
            $query = "SELECT DISTINCT `id`, `publishedTs`, `weight` FROM `{$table}`";
        }

        if ($state !== null) {
            $wheres[] = "`state` = ?";
            $values[] = $state;
        }

        if (!empty($wheres)) {
            $query .= " WHERE " . implode(" AND ", $wheres);
        }

        if ($sortProperty !== null) {
            $query .= " ORDER BY ";
            $query .= "`{$sortProperty}` " . ($sortDirection === SORT_DESC ? "DESC" : "ASC");
            $query .= " LIMIT {$start}, {$num}";
        }

        $stmt = $db->prepare($query);
        $stmt->execute($values);
        $pages = [];
        $sortFactors = [];
        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $ids[] = $item['id'];
            if ($sortProperty === null) {
                $sortFactors[] = self::getSortFactor($item['weight'], $item['publishedTs'], $item['id']);
            }
        }

        if ($sortProperty === null) {
            array_multisort($sortFactors, SORT_DESC, $ids);
        }

        foreach ($ids as $i => $id) {
            if ($sortProperty === null && $i < $start) {
                continue;
            }
            $pages[] = self::getLandingPage($id);
            if (count($pages) >= $num) {
                break;
            }
        }

        return $pages;
    }

    /**
     * @param int $weight
     * @param int $publishedTs
     * @param int $id
     * @return float
     */
    protected static function getSortFactor(int $weight, int $publishedTs, int $id): float
    {

        // 1 year = 1 point per punish weight
        //Time::getNow() - Time::ONE_YEAR // one year ago
        // ($publishedTs - (Time::getNow() - Time::ONE_YEAR)) / Time::ONE_YEAR // this is 0-1 of a year gone since publish

        $punishWeight = 11 - $weight;

        // got to see how old the article is. 1 = ten years or more. 0 = brand new.

        $max = Time::getNow() - (Time::ONE_YEAR * 10);
        $min = Time::getNow();
        $oldFactor = ($publishedTs - $min) / ($max - $min);
        $datePunish = ($oldFactor * $punishWeight);

        $weightFactor = 10 + ($weight / 2);
        $factor = $weightFactor - $datePunish;

        return $factor;
    }

    /**
     * gets a cache key
     * @param string $type
     * @param string|int $identifier
     * @return string
     */
    protected static function getCacheKey(string $type, string|int $identifier): string
    {
        return implode(':', [static::class, static::getCacheVersion(), $type, $identifier]);
    }

    /**
     * delete it from the cache if it's there
     * @param array $changedProperties
     * @return void
     * @throws ValidationException
     */
    protected function afterSaveUpdate(array $changedProperties): void
    {
        $this->invalidateCache();
    }

    /**
     * @param int $id
     * @return LandingPage|null
     */
    public static function getLandingPage(int $id): ?LandingPage
    {
        $page = static::readFromId($id);
        if (!$page) {
            return null;
        }

        if (!isset($page->numTags)) {
            $page->numTags = 0;
        }
        $page->numTags = count(TaggedContent::getFromContentItem(get_class($page), $page->id));
        return $page;
    }

    /**
     * @param string $prop
     * @return mixed
     */
    public function __get(string $prop): mixed
    {

        return match ($prop) {
            'timestamp' => $this->publishedTs,
            'displayLevel' => $this->state,
            'url', 'link' => $this->getUrl(),
            'editId' => $this->id ?? 'new',
            'contentParts' => $this->getContentParts(),
            default => (property_exists($this, $prop) && isset($this->$prop)) ? $this->$prop : null
        };
    }

    protected function getContentParts(): array
    {
        if (!$this->processedDisplayParts) {
            // Regex pattern to match insider roadblock placeholder regardless of whitespace and flexible p tag content
            $roadblockPattern = '/\s*<div\s+class="roadblock">\s*<h3\s+class="text-center">Insider\s+Sign-Up\s+Placeholder<\/h3>\s*<p\s+class="align-center">[^<]*<\/p>\s*<\/div>\s*/i';
            $this->processedDisplayParts = preg_split($roadblockPattern, $this->content);
        }
        return $this->processedDisplayParts ?? [];
    }

    /**
     * @return void
     * @throws ValidationException
     */
    public function checkReadyForPublishing(): void
    {
        $errors = [];

        // missing article body
        if (empty($this->content)) {
            $errors[] = "Article body is missing";
        }

        // missing url
        if (empty($this->urlStub)) {
            $errors[] = "URL is missing";
        }

        // missing title
        if (empty($this->title)) {
            $errors[] = "Title is missing";
        }

        // missing description
        if (empty($this->description)) {
            $errors[] = "Description is missing";
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @return void add custom validations for a landing page
     * @throws ValidationException
     */
    protected function customValidate(): void
    {
        $errors = [];

        // check the url stub is valid
        /*if (!empty($this->urlStub)) {
            $router = Router::getInstance();
            if (!$router->pathIsAvailable($this->urlStub)) {
                $errors[] = 'The URL ' . $this->urlStub . ' is already in use. Try a different URL for this landing page.';
            }
        }*/

        // if the landing page is set to published, make sure it has both a title and a url stub
        if ($this->state === self::STATE_PUBLISHED) {
            if (empty($this->title)) {
                $errors[] = 'A title is required for a published landing page';
            }
            if (empty($this->urlStub)) {
                $errors[] = 'A URL is required for a published landing page';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /** @inheritDoc */
    public function getTitle(): string
    {
        return $this->title;
    }

    /** @inheritDoc */
    public function getUrl(): string
    {
        return $this->urlStub;
    }

    public function getEditUrl(): ?string
    {
        return 'staff/landing-pages/edit?landingpageid=' . $this->id;
    }

    /** @inheritDoc */
    public function getTs(): int
    {
        return $this->publishedTs;
    }

    /** @inheritDoc */
    public function isPublished(): bool
    {
        return $this->state === self::STATE_PUBLISHED && $this->publishedTs <= Time::getNow();
    }

    /** @inheritDoc */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /** @inheritDoc */
    public static function getReadableContentType(): string
    {
        return 'Landing Page';
    }

    /** @inheritDoc */
    public function getSubtitle(): string
    {
        return '';
    }

    /** @inheritDoc */
    public function getDescription(): string
    {
        return $this->description;
    }

    /** @inheritDoc */
    public function getThumbnailSrc(): string
    {
        return $this->thumbnailSrc;
    }

    /** @inheritDoc */
    public function getVThumbnailSrc(): string
    {
        return '';
    }

    /** @inheritDoc */
    public function getAlwaysCurrent(): bool
    {
        return true;
    }

    /** @inheritDoc */
    public function getSitemap(): bool
    {
        return true;
    }

    /** @inheritDoc */
    static public function getImpersistentPageEntryFields(): array
    {
        return ['sitemap', 'alwaysCurrent', 'vThumbnailSrc', 'subtitle'];
    }

    /** @inheritDoc */
    protected function getPageEntryRelevantProperties(): array
    {
        return [
            'title',        // getTitle()
            'description',  // getDescription()
            'thumbnailSrc', // getThumbnailSrc()
            'path',         // getUrl()
            'weight',       // getWeight()
        ];
    }

    /** @inheritDoc */
    public function updateFromPageEntry(PageEntry $pageEntry): void
    {
        $this->updateFromPageEntry = true;
        $this->update([
            'weight' => $pageEntry->weight,
            'title' => $pageEntry->title,
            'thumbnailSrc' => $pageEntry->thumbnailSrc,
            'description' => $pageEntry->description
        ]);
        $this->updateFromPageEntry = false;
    }

    /**
     * @inheritDoc
     */
    public function getCreatorId(): ?int
    {
        return null;
    }

    public static function getContentItem(int|string $id): ?Content
    {
        $page = static::readFromId($id);
        $page->numTags = count(TaggedContent::getFromContentItem(get_class($page), $page->id));
        return $page;
    }
}
