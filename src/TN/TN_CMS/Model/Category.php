<?php

namespace TN\TN_CMS\Model;

use TN\TN_Core\Attribute\MySQL\AutoIncrement;
use TN\TN_Core\Attribute\MySQL\PrimaryKey;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Storage\Cache;
use TN\TN_Core\Model\Time\Time;

/**
 * a category is an elevated tag. it can have its own page for indexing content.
 * 
 */
#[TableName('cms_categories')]
class Category implements Persistence
{
    use PersistentModel;
    use MySQL;

    /** @var string the readable category */
    public string $text;

    /** @var string */
    public string $tagText;

    /**
     * get all the categories
     * @return array
     */
    public static function getAll(): array
    {
        $cacheKey = get_called_class() . '-v4-all';
        $cachedCategories = Cache::get($cacheKey);
        if ($cachedCategories) {
            return $cachedCategories;
        }
        $categories = self::readAll();
        Cache::set($cacheKey, $categories, Time::ONE_HOUR);
        return $categories;
    }

    /**
     * translates a category text into its corresponding tag text
     * @param int $categoryId
     * @return string|null
     */
    public static function getTagTextFromId(int $categoryId): ?string
    {
        foreach (self::getAll() as $category) {
            if ($category->id === $categoryId) {
                return $category->tagText;
            }
        }
        return null;
    }

    /**
     * translates a category text into its corresponding tag text
     * @param string $categoryText
     * @return string|null
     */
    public static function getTagTextFromText(string $categoryText): ?string
    {
        foreach (self::getAll() as $category) {
            if (strtolower($category->text) === strtolower($categoryText)) {
                return $category->tagText;
            }
        }
        return null;
    }
}
