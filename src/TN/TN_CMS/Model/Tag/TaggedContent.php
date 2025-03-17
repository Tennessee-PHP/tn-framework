<?php

namespace TN\TN_CMS\Model\Tag;

use PDO;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\Relationships\ParentId;
use TN\TN_Core\Attribute\Relationships\ParentObject;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Storage\DB;

#[TableName('cms_tagged_content')]
class TaggedContent implements Persistence
{
    use MySQL;
    use PersistentModel;

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
        $stmt = $db->prepare("
            SELECT *
            FROM
                {$table} as c
            WHERE
                c.contentClass = ?
                AND c.contentId = ?
                AND c.tagId = ?
                ");
        $stmt->execute([$contentClass, $contentId, $tag->id]);
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
        // lookup TaggedContent instances and return the tag of each for the specified content
        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $table = self::getTableName();
        $tagsTable = Tag::getTableName();
        $stmt = $db->prepare("
            SELECT
                c.*,
                t.id as tag_id, t.itemType as tag_itemType, t.itemId as tag_itemId, t.text as tag_text
            FROM
                {$table} as c, {$tagsTable} as t
            WHERE
                c.contentClass = ?
                AND c.contentId = ?
                AND c.tagId = t.id
                ");
        $stmt->execute([$contentClass, $contentId]);
        $taggedContents = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $taggedContent = TaggedContent::getInstance();
            foreach (['id', 'contentClass', 'contentId', 'tagId', 'primary'] as $property) {
                if (!empty($row[$property])) {
                    $taggedContent->$property = $row[$property];
                }
            }
            $tag = Tag::getInstance();
            foreach (['id', 'itemType', 'itemId', 'text'] as $property) {
                $tag->$property = $row['tag_' . $property];
            }
            $taggedContent->tag = $tag;
            $taggedContents[] = $taggedContent;
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

}