<?php

namespace TN\TN_Core\Model\PersistentModel\Trait;

use TN\TN_CMS\Model\Tag\Tag;
use TN\TN_CMS\Model\Tag\TaggedContent;

/**
 * Trait for models that can be tagged using the TN_CMS tagging system
 * 
 * Provides convenience methods that wrap TaggedContent static methods
 * for easier use on models that implement tagging functionality.
 */
trait Taggable
{
    /**
     * Get all tags associated with this model
     * 
     * @return Tag[] Array of tag objects
     */
    public function getTags(): array
    {
        $taggedContents = TaggedContent::getFromContentItem(static::class, $this->id);
        $tags = [];
        
        foreach ($taggedContents as $taggedContent) {
            $tags[] = $taggedContent->tag;
        }
        
        return $tags;
    }

    /**
     * Add a tag to this model
     * 
     * @param Tag $tag Tag to add
     * @param bool $primary Whether this is a primary tag
     * @return TaggedContent Created TaggedContent object
     */
    public function addTag(Tag $tag, bool $primary = false): TaggedContent
    {
        return TaggedContent::addTag(static::class, $this->id, $tag, $primary);
    }

    /**
     * Remove a tag from this model
     * 
     * @param Tag $tag Tag to remove
     * @return bool True if tag was removed, false if not found
     */
    public function removeTag(Tag $tag): bool
    {
        return TaggedContent::removeTag(static::class, $this->id, $tag);
    }

    /**
     * Set tags for this model (alias for TaggedContent::setTags)
     * 
     * @param Tag[] $tags Array of tag objects to associate
     * @param bool $eraseExisting Whether to remove existing tags first
     * @return TaggedContent[] Array of created TaggedContent objects
     */
    public function setTags(array $tags, bool $eraseExisting = true): array
    {
        return TaggedContent::setTags(static::class, $this->id, [], $tags, $eraseExisting);
    }
}
