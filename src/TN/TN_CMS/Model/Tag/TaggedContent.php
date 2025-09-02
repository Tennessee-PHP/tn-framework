<?php

namespace TN\TN_CMS\Model\Tag;

use PDO;
use TN\TN_CMS\Model\PageEntry;
use TN\TN_Core\Attribute\Cache;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\Relationships\ParentId;
use TN\TN_Core\Attribute\Relationships\ParentObject;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Trait\PerformanceRecorder;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;

#[TableName('cms_tagged_content')]
#[Cache(version: '1.0', lifespan: 3600)] // Cache for 1 hour
class TaggedContent implements Persistence
{
    use MySQL;
    use PersistentModel;
    use PerformanceRecorder;

    public string $contentClass = '';
    public string $contentId = '';
    #[ParentId]
    public int $tagId = 0;
    public bool $primary = false;
    #[ParentObject]
    public Tag $tag;
    #[Impersistent]
    public string $title;

    /**
     * @param string $contentClass
     * @param string $contentId
     * @param Tag[] $primaryTags
     * @param Tag[] $tags
     * @param bool $eraseExisting
     * @return TaggedContent[]
     * @throws ValidationException
     */
    public static function setTags(string $contentClass, string $contentId, array $primaryTags, array $tags, bool $eraseExisting = true): array
    {
        if ($eraseExisting) {
            $existing = TaggedContent::getFromContentItem($contentClass, $contentId);
            TaggedContent::batchErase($existing);
        }

        $taggedContents = [];

        foreach ($primaryTags as $tag) {
            $taggedContent = TaggedContent::getInstance();
            $taggedContent->update([
                'contentClass' => $contentClass,
                'contentId' => $contentId,
                'tagId' => $tag->id,
                'primary' => true
            ]);
            $taggedContents[] = $taggedContent;
        }
        foreach ($tags as $tag) {
            $taggedContent = TaggedContent::getInstance();
            $taggedContent->update([
                'contentClass' => $contentClass,
                'contentId' => $contentId,
                'tagId' => $tag->id,
                'primary' => false
            ]);
            $taggedContents[] = $taggedContent;
        }

        // we can now update numTags on the page entry
        $pageEntry = PageEntry::getPageEntryForContentItem($contentClass, $contentId);
        $pageEntry?->update([
            'numTags' => count($taggedContents)
        ]);

        return $taggedContents;
    }

    public static function contentItemHasTag(string $contentClass, string $contentId, Tag $tag): bool
    {
        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $table = self::getTableName();
        $query = "
            SELECT *
            FROM
                {$table} as c
            WHERE
                c.contentClass = ?
                AND c.contentId = ?
                AND c.tagId = ?
                ";
        $event = self::startPerformanceEvent('MySQL', $query, ['params' => [$contentClass, $contentId, $tag->id]]);
        $stmt = $db->prepare($query);
        $stmt->execute([$contentClass, $contentId, $tag->id]);
        $event?->end();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return count($results) > 0;
    }

    /**
     * @param string $contentClass
     * @param string $contentId
     * @return TaggedContent[]
     */
    public static function getFromContentItem(string $contentClass, string $contentId): array
    {
        error_log("TAGGEDCONTENT DEBUG: getFromContentItem called for $contentClass ID $contentId");
        
        // Use framework's cached search system
        $searchArgs = new SearchArguments([
            new SearchComparison('`contentClass`', '=', $contentClass),
            new SearchComparison('`contentId`', '=', $contentId)
        ]);
        
        $taggedContents = self::search($searchArgs);
        error_log("TAGGEDCONTENT DEBUG: Found " . count($taggedContents) . " tagged contents");
        
        // Bulk load all tags at once instead of N+1 queries
        $tagIds = [];
        foreach ($taggedContents as $taggedContent) {
            $tagIds[] = $taggedContent->tagId;
        }
        
        if (!empty($tagIds)) {
            error_log("TAGGEDCONTENT DEBUG: Bulk loading Tag IDs: " . implode(', ', $tagIds));
            $tagsById = Tag::readFromIds($tagIds);
            
            // Assign the loaded tags to each TaggedContent
            foreach ($taggedContents as $taggedContent) {
                $taggedContent->tag = $tagsById[$taggedContent->tagId] ?? null;
            }
        }

        // let's erase any duplicates
        $byTag = [];
        foreach ($taggedContents as $taggedContent) {
            if (!isset($byTag[$taggedContent->tag->text])) {
                $byTag[$taggedContent->tag->text] = [];
            }
            $byTag[$taggedContent->tag->text][] = $taggedContent;
        }
        foreach ($byTag as $tag => $tcs) {
            if (count($tcs) === 1) {
                continue;
            }
            $primaries = [];
            unset($tc);
            foreach ($tcs as $tc) {
                $primaries[] = $tc->primary;
            }
            array_multisort($primaries, SORT_DESC, $tcs);
            unset($tc);
            unset($i);
            foreach ($tcs as $i => $tc) {
                if ($i === 0) {
                    continue;
                }
                $tc->erase();
                array_splice($taggedContents, array_search($tc, $taggedContents), 1);
            }
        }

        return $taggedContents;
    }

    /**
     * Add a single tag to content item
     * 
     * @param string $contentClass
     * @param string $contentId
     * @param Tag $tag
     * @param bool $primary
     * @return TaggedContent
     * @throws ValidationException
     */
    public static function addTag(string $contentClass, string $contentId, Tag $tag, bool $primary = false): TaggedContent
    {
        // Check if tag is already associated
        if (self::contentItemHasTag($contentClass, $contentId, $tag)) {
            throw new \InvalidArgumentException("Tag '{$tag->text}' is already associated with this content item");
        }

        $taggedContent = self::getInstance();
        $taggedContent->update([
            'contentClass' => $contentClass,
            'contentId' => $contentId,
            'tagId' => $tag->id,
            'primary' => $primary
        ]);

        // Update numTags on the page entry
        $pageEntry = PageEntry::getPageEntryForContentItem($contentClass, $contentId);
        if ($pageEntry) {
            $pageEntry->updateNumTags();
        }

        return $taggedContent;
    }

    /**
     * Remove a tag from content item
     * 
     * @param string $contentClass
     * @param string $contentId
     * @param Tag $tag
     * @return bool True if tag was removed, false if not found
     */
    public static function removeTag(string $contentClass, string $contentId, Tag $tag): bool
    {
        $existingTags = self::getFromContentItem($contentClass, $contentId);
        
        foreach ($existingTags as $taggedContent) {
            if ($taggedContent->tagId === $tag->id) {
                $taggedContent->erase();
                
                // Update numTags on the page entry
                $pageEntry = PageEntry::getPageEntryForContentItem($contentClass, $contentId);
                if ($pageEntry) {
                    $pageEntry->updateNumTags();
                }
                
                return true;
            }
        }
        
        return false;
    }

}