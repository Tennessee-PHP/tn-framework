<?php

namespace TN\TN_Core\Test;

/**
 * Test Factory
 * Base class for creating test data factories with common patterns.
 * 
 * This class provides utilities for:
 * - Generating realistic test data
 * - Managing sequences and uniqueness
 * - Creating related entities
 * - Customizing factory behavior
 */
abstract class TestFactory
{
    protected static array $sequences = [];
    protected TestDataManager $dataManager;
    protected array $defaultAttributes = [];

    public function __construct(TestDataManager $dataManager)
    {
        $this->dataManager = $dataManager;
    }

    /**
     * Create an entity with the given attributes
     * 
     * @param array $attributes Override attributes
     * @return mixed Created entity
     */
    abstract public function create(array $attributes = []);

    /**
     * Get default attributes for the entity
     * 
     * @return array Default attributes
     */
    abstract protected function getDefaultAttributes(): array;

    /**
     * Merge default attributes with overrides
     * 
     * @param array $overrides Override attributes
     * @return array Merged attributes
     */
    protected function mergeAttributes(array $overrides = []): array
    {
        return array_merge($this->getDefaultAttributes(), $overrides);
    }

    /**
     * Generate a unique sequence number for a given key
     * 
     * @param string $key Sequence key
     * @return int Next sequence number
     */
    protected function sequence(string $key): int
    {
        if (!isset(static::$sequences[$key])) {
            static::$sequences[$key] = 0;
        }
        return ++static::$sequences[$key];
    }

    /**
     * Reset sequence counter for a key
     * 
     * @param string $key Sequence key
     * @return void
     */
    protected function resetSequence(string $key): void
    {
        static::$sequences[$key] = 0;
    }

    /**
     * Reset all sequences
     * 
     * @return void
     */
    public static function resetAllSequences(): void
    {
        static::$sequences = [];
    }

    /**
     * Generate a unique email address
     * 
     * @param string $prefix Email prefix
     * @return string Unique email
     */
    protected function uniqueEmail(string $prefix = 'test'): string
    {
        $sequence = $this->sequence('email');
        return "{$prefix}{$sequence}@example.com";
    }

    /**
     * Generate a unique username
     * 
     * @param string $prefix Username prefix
     * @return string Unique username
     */
    protected function uniqueUsername(string $prefix = 'user'): string
    {
        $sequence = $this->sequence('username');
        return "{$prefix}{$sequence}";
    }

    /**
     * Generate a unique name
     * 
     * @param string $prefix Name prefix
     * @return string Unique name
     */
    protected function uniqueName(string $prefix = 'Test'): string
    {
        $sequence = $this->sequence('name');
        return "{$prefix} {$sequence}";
    }

    /**
     * Generate a unique slug
     * 
     * @param string $prefix Slug prefix
     * @return string Unique slug
     */
    protected function uniqueSlug(string $prefix = 'test'): string
    {
        $sequence = $this->sequence('slug');
        return "{$prefix}-{$sequence}";
    }

    /**
     * Pick a random element from an array
     * 
     * @param array $array Array to pick from
     * @return mixed Random element
     */
    protected function randomElement(array $array)
    {
        return $array[array_rand($array)];
    }

    /**
     * Generate a random date within a range
     * 
     * @param string $start Start date (default: now)
     * @param string $end End date (default: +1 year)
     * @return string Random date in Y-m-d H:i:s format
     */
    protected function randomDate(string $start = 'now', string $end = '+1 year'): string
    {
        $startTimestamp = strtotime($start);
        $endTimestamp = strtotime($end);
        $randomTimestamp = mt_rand($startTimestamp, $endTimestamp);

        return date('Y-m-d H:i:s', $randomTimestamp);
    }

    /**
     * Generate a future date
     * 
     * @param string $offset Offset from now (default: +1 day)
     * @return string Future date in Y-m-d H:i:s format
     */
    protected function futureDate(string $offset = '+1 day'): string
    {
        return date('Y-m-d H:i:s', strtotime($offset));
    }

    /**
     * Generate a past date
     * 
     * @param string $offset Offset from now (default: -1 day)
     * @return string Past date in Y-m-d H:i:s format
     */
    protected function pastDate(string $offset = '-1 day'): string
    {
        return date('Y-m-d H:i:s', strtotime($offset));
    }

    /**
     * Generate a random boolean
     * 
     * @param float $trueChance Probability of true (0.0 to 1.0)
     * @return bool Random boolean
     */
    protected function randomBoolean(float $trueChance = 0.5): bool
    {
        return mt_rand() / mt_getrandmax() < $trueChance;
    }

    /**
     * Generate a random integer within a range
     * 
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @return int Random integer
     */
    protected function randomInt(int $min = 1, int $max = 100): int
    {
        return mt_rand($min, $max);
    }

    /**
     * Generate a random float within a range
     * 
     * @param float $min Minimum value
     * @param float $max Maximum value
     * @param int $decimals Number of decimal places
     * @return float Random float
     */
    protected function randomFloat(float $min = 0.0, float $max = 100.0, int $decimals = 2): float
    {
        $random = mt_rand() / mt_getrandmax();
        $value = $min + ($random * ($max - $min));
        return round($value, $decimals);
    }

    /**
     * Generate lorem ipsum text
     * 
     * @param int $words Number of words
     * @return string Lorem ipsum text
     */
    protected function loremIpsum(int $words = 10): string
    {
        $loremWords = [
            'lorem',
            'ipsum',
            'dolor',
            'sit',
            'amet',
            'consectetur',
            'adipiscing',
            'elit',
            'sed',
            'do',
            'eiusmod',
            'tempor',
            'incididunt',
            'ut',
            'labore',
            'et',
            'dolore',
            'magna',
            'aliqua',
            'enim',
            'ad',
            'minim',
            'veniam',
            'quis',
            'nostrud',
            'exercitation',
            'ullamco',
            'laboris',
            'nisi',
            'aliquip',
            'ex',
            'ea',
            'commodo',
            'consequat',
            'duis',
            'aute',
            'irure',
            'in',
            'reprehenderit',
            'voluptate',
            'velit',
            'esse',
            'cillum',
            'fugiat',
            'nulla',
            'pariatur',
            'excepteur',
            'sint',
            'occaecat',
            'cupidatat',
            'non',
            'proident',
            'sunt',
            'culpa',
            'qui',
            'officia',
            'deserunt',
            'mollit',
            'anim',
            'id',
            'est',
            'laborum'
        ];

        $selectedWords = [];
        for ($i = 0; $i < $words; $i++) {
            $selectedWords[] = $loremWords[array_rand($loremWords)];
        }

        return implode(' ', $selectedWords);
    }

    /**
     * Create a related entity using another factory
     * 
     * @param string $entityType Entity type to create
     * @param array $attributes Override attributes
     * @return mixed Created related entity
     */
    protected function createRelated(string $entityType, array $attributes = [])
    {
        return $this->dataManager->create($entityType, $attributes);
    }

    /**
     * Get the data manager instance
     * 
     * @return TestDataManager Data manager
     */
    protected function getDataManager(): TestDataManager
    {
        return $this->dataManager;
    }
}
