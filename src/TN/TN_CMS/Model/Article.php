<?php

namespace TN\TN_CMS\Model;

use TN\TN_Core\Model\Package\Stack;
use PDO;
use TN\TN_Advert\Model\Advert;
use TN\TN_CMS\Model\Tag\Tag;
use TN\TN_CMS\Model\Tag\TaggedContent;
use TN\TN_Core\Attribute\Constraints\OnlyContains;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Attribute\Cache as CacheAttribute;
use TN\TN_Core\Model\Storage\Cache;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;

// reference can be removed post-migration

/**
 * $article->author
 * a single article in a content management system
 *
 *
 * @property-read User $author instance of the user who is the author of this article
 */
#[TableName('cms_articles')]
#[CacheAttribute(version: '1.2', lifespan: 3600)]
class Article extends Content implements Persistence
{
    use MySQL;
    use PersistentModel;

    const int STATE_DRAFT = 1;
    const int STATE_READY_FOR_EDITING = 2;
    const int STATE_PUBLISHED = 3;
    const int STATE_TEMPLATE = 4;

    const string ROADBLOCK_PLACEHOLDER = '<div class="roadblock"><h3 class="text-center">Roadblock Placeholder</h3><p class="align-center">If the user needs to pay to see the full article, this is where the roadblock will be.</p></div>';
    const string ADVERT_PLACEHOLDER = '<div class="py-2 px-4 mb-2 bg-light rounded-3"><div class="container-fluid py-2"><h2 class="fw-bold">Advertisement Placeholder</h2><p class="col-md-8 fs-4">When the article is published, this placeholder will be switched out for an ad.</p><button class="btn btn-primary btn-lg" type="button">Let\'s Go!</button></div></div>';

    /**
     * @return array
     */
    public static function getAllStates(): array
    {
        return [
            self::STATE_DRAFT => 'Draft',
            self::STATE_READY_FOR_EDITING => 'Ready for Editing',
            self::STATE_PUBLISHED => 'Published',
            self::STATE_TEMPLATE => 'Templates'
        ];
    }

    use MySQL;

    public string $title = '';
    public int $authorId = 0;
    public string $description = '';
    public string $thumbnailSrc = '';
    public string $content = '';

    #[OnlyContains('A-Za-z0-9 _-', 'letters, numbers, spaces, underscores and dashes')]
    public string $primarySeoKeyword = '';

    public string $urlStub = '';
    public string $contentRequired = '';
    public int $publishedTs = 0;
    public int $state = self::STATE_DRAFT;
    public int $weight = 5;

    // todo: need to be moved as extensions to another package
    public int $week = 0;
    public int $year = 0;

    #[Impersistent]
    public int $numTags = 0;

    #[Impersistent]
    public string $authorAvatarUrl = '';

    #[Impersistent]
    public ?User $author = null;

    #[Impersistent]
    public ?string $processedDisplayContent = null;

    /** @var bool flag to track if update is from page entry */
    #[Impersistent]
    protected bool $updateFromPageEntry = false;

    /**
     * loads up an object from mysql, given its id
     * @param string $urlStub
     * @return Article|null
     */
    public static function readFromUrlStub(string $urlStub): ?Article
    {
        return static::searchOne(new SearchArguments(new SearchComparison('`urlStub`', '=', $urlStub)));
    }

    /**
     * get all the objects stored on this class
     * @param array $filters : an array of filters. Supported keys are authorId, state, category, categoryId, tag. See the examples given.
     * @param string|null $sortProperty
     * @param int|null $sortDirection
     * @param int $start
     * @param int $num
     * @return array
     * @example Article::getArticles([ 'tag' => 'idp' ], 'weight', SORT_ASC, 0, 50);
     * @example Article::getArticles([ 'authorId' => 54, 'state' => Article::STATE_PUBLISHED, 'category' => 'dynasty' ], null, null, 0, 50);
     */
    public static function getArticles(
        array $filters = [],
        ?string $sortProperty = null,
        ?int $sortDirection = null,
        int   $start = 0,
        int $num = 100,
        bool $count = false
    ): array|int {
        if (!$sortDirection) {
            $sortDirection = SORT_DESC;
        }

        $authorId = $filters['authorId'] ?? null;
        $state = $filters['state'] ?? null;
        $category = $filters['category'] ?? null;
        $tag = $filters['tag'] ?? null;
        $categoryId = $filters['categoryId'] ?? null;
        $playerId = $filters['playerId'] ?? null;
        $inPast = $filters['inPast'] ?? false;

        if ($category) {
            // translate category into tag
            $tag = $category;
        } else if ($categoryId) {
            $tag = Category::getTagTextFromId($categoryId);
        }

        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $table = self::getTableName();

        $values = [];
        $wheres = [];
        if ($tag) {
            $tagsTable = Tag::getTableName();
            $taggedContentsTable = TaggedContent::getTableName();
            $query = "SELECT " .
                ($count ? "count(DISTINCT a.`id`)" : "DISTINCT a.`id`, a.`publishedTs`, a.`weight` ") .
                "FROM `{$table}` as a, {$tagsTable} as t, {$taggedContentsTable} as c";
            $wheres[] = "t.`text` LIKE ?";
            $values[] = $tag;
            $wheres[] = "t.`id` = c.`tagId`";
            $wheres[] = "c.`contentClass` = ?";
            $values[] = get_called_class();
            $wheres[] = "c.`contentId` = `a`.id";
        } else {
            $query = "SELECT " .
                ($count ? "count(DISTINCT `id`) " : " DISTINCT `id`, `publishedTs`, `weight` ") .
                " FROM `{$table}`";
        }

        if ($inPast) {
            $wheres[] = "`publishedTs` <= ?";
            $values[] = Time::getNow();
        }

        if ($authorId !== null) {
            $wheres[] = "`authorId` = ?";
            $values[] = $authorId;
        }

        if ($state !== null) {
            $wheres[] = "`state` = ?";
            $values[] = $state;
        }

        if (!empty($wheres)) {
            $query .= " WHERE " . implode(" AND ", $wheres);
        }

        if ($count) {
            $stmt = $db->prepare($query);
            $stmt->execute($values);
            $result = $stmt->fetch(PDO::FETCH_NUM);
            return (int)$result[0];
        }

        if ($sortProperty !== null) {
            $query .= " ORDER BY ";
            $query .= "`{$sortProperty}` " . ($sortDirection === SORT_DESC ? "DESC" : "ASC");
            $query .= " LIMIT {$start}, {$num}";
        }

        $stmt = $db->prepare($query);
        $stmt->execute($values);
        $articles = [];
        $sortFactors = [];
        $timestamps = [];
        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $ids[] = $item['id'];
            if ($sortProperty === null) {
                $sortFactors[] = self::getSortFactor($item['weight'], $item['publishedTs'], $item['id']);
                $timestamps[] = $item['publishedTs'];
            }
        }

        if ($sortProperty === null) {
            array_multisort($sortFactors, SORT_DESC, $timestamps, SORT_DESC, $ids);
        }

        foreach ($ids as $i => $id) {
            if ($sortProperty === null && $i < $start) {
                continue;
            }
            $article = static::readFromId($id);
            // Populate numTags field for the article list
            if ($article) {
                $article->numTags = count(TaggedContent::getFromContentItem(get_class($article), $article->id));
            }
            $articles[] = $article;
            if (count($articles) >= $num) {
                break;
            }
        }

        return $articles;
    }

    /**
     * @param int $weight
     * @param int $publishedTs
     * @param int $id
     * @return float
     */
    protected static function getSortFactor(int $weight, int $publishedTs, int $id): float
    {
        return self::calculateTimeFactor($publishedTs) + $weight;
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
        while ($i < count($tsScale) - 1 && $diff < $tsScale[$i]) {
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

    /**
     * delete it from the cache if it's there
     * @param array $changedProperties
     * @return void
     * @throws ValidationException
     */

    protected function afterSaveUpdate(array $changedProperties): void
    {
        if (empty($this->urlStub)) {
            if (!empty(array_intersect(['title'], $changedProperties))) {
                $this->reformUrlStub();
            }
        }

        // Check if published status might have changed and update PageEntry accordingly
        if (!$this->updateFromPageEntry && !empty(array_intersect(['state', 'publishedTs', 'weight'], $changedProperties))) {
            $this->writeToPageEntry();
        }

        // let's invalidate the cache
        $cacheKey = self::getCacheKey('getcontentitem', $this->id);
        Cache::delete($cacheKey);
    }

    /**
     * @param Article $template
     * @return void
     * @throws ValidationException
     */
    public function copyFromTemplate(Article $template): void
    {
        $author = User::readFromId($this->authorId);
        $taggedContent = TaggedContent::getFromContentItem(get_class($this), $template->id);

        $this->update([
            'publishedTs' => Time::getNow(),
            'authorId' => $author->id,
            'authorAvatarUrl' => $this->getAvatarUrl($author instanceof User ? $author : null),
            'title' => $template->title,
            'weight' => $template->weight,
            'urlStub' => $template->urlStub,
            'content' => $template->content,
            'primarySeoKeyword' => $template->primarySeoKeyword,
            'contentRequired' => $template->contentRequired,
            'thumbnailSrc' => $template->thumbnailSrc,
            'numTags' => count($taggedContent)
        ]);

        foreach ($taggedContent as $tag) {
            $taggedContent = TaggedContent::getInstance();
            $taggedContent->update([
                'contentClass' => get_class($template),
                'contentId' => $this->id,
                'tagId' => $taggedContent->tagId,
                'primary' => false
            ]);
        }
    }

    /**
     * @param int|string $id
     * @return Article|null
     */
    public static function getContentItem(int|string $id): ?Article
    {
        $cacheKey = self::getCacheKey('getcontentitem', $id);
        $cachedArticle = Cache::get($cacheKey);
        if ($cachedArticle) {
            return $cachedArticle;
        }

        $article = self::readFromId($id) ?? null;
        if (!$article) {
            return null;
        }

        $author = User::readFromId($article->authorId);
        $article->numTags = count(TaggedContent::getFromContentItem(get_class($article), $article->id));
        $article->authorAvatarUrl = $article->getAvatarUrl($author instanceof User ? $author : null);

        Cache::set($cacheKey, $article, Time::ONE_HOUR);
        return $article;
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
            'author' => $this->getAuthor(),
            'authorName' => $this->getAuthor() ? $this->getAuthor()->name : ($_ENV['SITE_NAME'] . ' Staff'),
            'authorAvatarUrl' => $this->getAvatarUrl($this->getAuthor()),
            'textPreRoadblock' => $this->textPreRoadblock(),
            'textPostRoadblock' => $this->textPostRoadblock(),
            'url', 'link' => $this->getUrl(),
            'image' => $this->thumbnailSrc,
            'editId' => $this->id ?? 'new',
            'displayContent' => $this->getDisplayContent(),
            'showArticleImageAtTop' => false,
            default => (property_exists($this, $prop) && isset($this->$prop)) ? $this->$prop : null
        };
    }

    protected function getDisplayContent(): string
    {
        if (!$this->processedDisplayContent) {
            $this->processedDisplayContent = $this->replaceAdvertPlaceholders(str_replace(PHP_EOL, "", $this->content));
            $this->processedDisplayContent = str_replace('blockquote class="twitter-tweet"', 'blockquote class="twitter-tweet-toload"', $this->processedDisplayContent);
        }
        $this->processedDisplayContent = str_replace('https://staging-www.fbg-dev.com/', $_ENV['BASE_URL'], $this->processedDisplayContent);
        return $this->processedDisplayContent ?? '';
    }

    protected function getAuthor(): ?User
    {
        if ($this->authorId === 0) {
            return null;
        }
        if (!$this->author) {
            $this->author = User::readFromId($this->authorId);
        }
        return $this->author instanceof User ? $this->author : null;
    }

    /**
     * @param User $user
     * @return string
     */
    public function getAvatarUrl(?User $user): string
    {
        return 'staffer-bio-images/default.png';
    }

    /**
     * @return void
     * @throws ValidationException
     */
    public function checkReadyForEditing(): void
    {
        $errors = [];

        // missing article body
        if (empty($this->content)) {
            $errors[] = "Article body is missing";
        }

        // missing main SEO keyword
        if (empty($this->primarySeoKeyword)) {
            $errors[] = "Main SEO keyword is missing";
        }

        // missing title
        if (empty($this->title)) {
            $errors[] = "Title is missing";
        }

        // missing description
        if (empty(trim($this->description))) {
            $errors[] = "Description is missing";
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
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

        // missing main SEO keyword
        if (empty($this->primarySeoKeyword)) {
            $errors[] = "Main SEO keyword is missing";
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
        if (empty(trim($this->description))) {
            $errors[] = "Description is missing";
        }

        // missing publish date
        if (empty($this->publishedTs)) {
            $errors[] = "Publish date is missing";
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param User $user
     * @return bool
     * is this user allowed to edit this article?
     */
    public function userCanEdit(User $user): bool
    {

        // is the user logged in? if not, return false
        if (!$user->loggedIn) {
            return false;
        }
        // does the user have article-author role, and ivs exactly the authorId of the article? return true
        if ($user->hasRole('article-author') && ($user->id === $this->authorId)) {
            return true;
        }

        // does the user have the article-editor role? return true
        if ($user->hasRole('article-editor')) {
            return true;
        }

        return false;
    }

    /**
     * after save insert
     */
    protected function afterSaveInsert(): void
    {
        if ($this->state !== self::STATE_PUBLISHED) {
            $this->reformUrlStub();
        }
    }

    /**
     * validate the url stub is unique
     * @return void
     * @throws ValidationException
     */
    protected function customValidate(): void
    {
        $errors = [];
        if (!empty($this->urlStub)) {
            if (!self::isUrlStubUnique($this->urlStub, $this->id ?? null)) {
                $errors[] = [
                    'property' => 'urlStub',
                    'error' => 'The URL ' . $this->urlStub . ' is already in use. Try a different URL for this article.'
                ];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * is the url stub unique?
     * @returns bool
     */
    protected static function isUrlStubUnique(string $urlStub, ?int $id): bool
    {
        foreach (
            self::searchByProperties([
                'urlStub' => $urlStub
            ]) as $match
        ) {
            if (!isset($id)) {
                return false;
            }
            if ($id !== $match->id) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return void
     * @throws ValidationException
     */
    protected function reformUrlStub(): void
    {
        if (empty($this->title)) {
            return;
        }

        $edits = [];

        $title = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $this->title));
        $commonWords = array(" of ", " and ", " the ");
        $title = str_replace($commonWords, "", $title);
        $title = preg_replace('/\b(a)\b/', '', $title);
        $title = trim($title);
        $title = str_replace(" ", "-", $title);

        $edits['urlStub'] = $title;

        // let's make sure that the urlStub is unique amongst articles
        $num = 0;
        $originalTitle = $edits['urlStub'];
        while (!self::isUrlStubUnique($edits['urlStub'], $this->id ?? null)) {
            $num += 1;
            $edits['urlStub'] = $originalTitle . '-' . $num;
        }

        $this->update($edits);
    }

    /**
     * @return string
     */
    protected function textPreRoadblock(): string
    {
        // Regex pattern to match roadblock placeholder regardless of whitespace
        $roadblockPattern = '/\s*<div\s+class="roadblock">\s*<h3\s+class="text-center">Roadblock\s+Placeholder<\/h3>\s*<p\s+class="align-center">If\s+the\s+user\s+needs\s+to\s+pay\s+to\s+see\s+the\s+full\s+article,\s+this\s+is\s+where\s+the\s+roadblock\s+will\s+be\.<\/p>\s*<\/div>\s*/i';

        $parts = preg_split($roadblockPattern, $this->displayContent);
        return $parts[0];
    }

    /**
     * @param $text
     * @return string
     */
    protected function replaceAdvertPlaceholders($text): string
    {
        $adverts = Advert::getShowableAdverts(User::getActive(), 'article_mid');
        shuffle($adverts);

        // Regex pattern to match advertisement placeholder regardless of whitespace
        $advertPattern = '/\s*<div\s+class="py-2\s+px-4\s+mb-2\s+bg-light\s+rounded-3">\s*<div\s+class="container-fluid\s+py-2">\s*<h2\s+class="fw-bold">Advertisement\s+Placeholder<\/h2>\s*<p\s+class="col-md-8\s+fs-4">When\s+the\s+article\s+is\s+published,\s+this\s+placeholder\s+will\s+be\s+switched\s+out\s+for\s+an\s+ad\.<\/p>\s*<button\s+class="btn\s+btn-primary\s+btn-lg"\s+type="button">Let\'s\s+Go!<\/button><\/div>\s*<\/div>\s*/i';

        while (preg_match($advertPattern, $text)) {
            $advertText = '';
            if (!empty($adverts)) {
                $advert = array_shift($adverts);
                $advertText = $advert->advert;
            }
            $text = preg_replace($advertPattern, $advertText, $text, 1);
        }
        return $text;
    }

    /**
     * @return string
     */
    protected function textPostRoadblock(): string
    {
        // Regex pattern to match roadblock placeholder regardless of whitespace  
        $roadblockPattern = '/\s*<div\s+class="roadblock">\s*<h3\s+class="text-center">Roadblock\s+Placeholder<\/h3>\s*<p\s+class="align-center">If\s+the\s+user\s+needs\s+to\s+pay\s+to\s+see\s+the\s+full\s+article,\s+this\s+is\s+where\s+the\s+roadblock\s+will\s+be\.<\/p>\s*<\/div>\s*/i';

        $parts = preg_split($roadblockPattern, $this->displayContent);
        return $parts[1] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): string
    {
        return 'article/' . $this->urlStub;
    }

    public function getEditUrl(): ?string
    {
        return 'staff/articles/edit?articleid=' . $this->id;
    }

    public function getTs(): int
    {
        return $this->publishedTs;
    }

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
        return 'Article';
    }

    /** @inheritDoc */
    public function getTitle(): string
    {
        return $this->title;
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
        return false;
    }

    /** @inheritDoc */
    static public function getImpersistentPageEntryFields(): array
    {
        return ['sitemap', 'alwaysCurrent', 'vThumbnailSrc', 'subtitle'];
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
        return $this->authorId;
    }
}
