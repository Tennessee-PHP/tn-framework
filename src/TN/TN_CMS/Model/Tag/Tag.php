<?php

namespace TN\TN_CMS\Model\Tag;

use PDO;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchLimit;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Attribute\Cache;

#[TableName('cms_tags')]
#[Cache(version: '1.0', lifespan: 3600)] // Cache for 1 hour
class Tag implements Persistence
{
    use MySQL;
    use PersistentModel;

    /** @var string|null e.g. player or team */
    public ?string $itemType = null;

    /** @var string|null the ID of the player/team */
    public ?string $itemId = null;

    /** @var string the readable tag */
    public string $text;

    /**
     * get all tags for a specific item type
     * @param string $itemType
     * @param bool $indexByItemId
     * @return array
     */
    public static function getItemTypeTags(string $itemType, bool $indexByItemId = false): array
    {
        $records = self::search(new SearchArguments(new SearchComparison('`itemType`', '=', $itemType)));
        if (!$indexByItemId) {
            return $records;
        }
        $recordsById = [];
        foreach ($records as $record) {
            $recordsById[$record->itemId] = $record;
        }
        return $recordsById;
    }

    /**
     * @param string $itemType
     * @param string $itemId
     * @return Tag|null
     */
    public static function getTagByItemTypeAndId(string $itemType, string $itemId): ?Tag
    {
        return static::searchOne(new SearchArguments([
            new SearchComparison('`itemType`', '=', $itemType),
            new SearchComparison('`itemId`', '=', $itemId)
        ]));
    }

    /**
     * gets an exact tag
     * @param string $text
     * @param bool $createIfDoesntExist
     * @return Tag|null
     * @throws ValidationException
     */
    public static function getExactTag(string $text, bool $createIfDoesntExist = false): ?Tag
    {
        $tag = static::searchOne(new SearchArguments(new SearchComparison('`text`', 'LIKE', $text)));
        if ($tag) {
            return $tag;
        }

        if (!$createIfDoesntExist) {
            return null;
        }

        $tag = Tag::getInstance();
        $tag->update([
            'text' => $text
        ]);
        return $tag;
    }

    /**
     * search tags!
     * @param string $term
     * @return array
     */
    public static function autocomplete(string $term): array
    {
        $tags = static::search(new SearchArguments(
            conditions: new SearchComparison('`text`', 'LIKE', '%' . strtolower($term) . '%'),
            limit: new SearchLimit(0, 10)));
        $results = [];
        foreach ($tags as $tag) {
            $results[] = [
                'label' => $tag->text,
                'value' => $tag->text
            ];
        }
        return $results;
    }
}