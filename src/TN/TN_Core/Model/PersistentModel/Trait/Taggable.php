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
    private ?array $cachedTags = null;
    
    public function getTags(): array
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = isset($backtrace[1]) ? $backtrace[1]['class'] . '::' . $backtrace[1]['function'] : 'unknown';
        error_log("TAGGABLE DEBUG: getTags() called on " . static::class . " ID {$this->id} by $caller");
        
        // Return cached tags if available
        if ($this->cachedTags !== null) {
            error_log("TAGGABLE DEBUG: Returning cached tags for " . static::class . " ID {$this->id}");
            return $this->cachedTags;
        }
        
        $taggedContents = TaggedContent::getFromContentItem(static::class, $this->id);
        $tags = [];
        
        foreach ($taggedContents as $taggedContent) {
            $tags[] = $taggedContent->tag;
        }
        
        // Cache the tags for subsequent calls
        $this->cachedTags = $tags;
        error_log("TAGGABLE DEBUG: Cached " . count($tags) . " tags for " . static::class . " ID {$this->id}");
        
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
        $result = TaggedContent::addTag(static::class, $this->id, $tag, $primary);
        // Clear cache since tags have changed
        $this->cachedTags = null;
        return $result;
    }

    /**
     * Remove a tag from this model
     * 
     * @param Tag $tag Tag to remove
     * @return bool True if tag was removed, false if not found
     */
    public function removeTag(Tag $tag): bool
    {
        $result = TaggedContent::removeTag(static::class, $this->id, $tag);
        // Clear cache since tags have changed
        $this->cachedTags = null;
        return $result;
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
        $result = TaggedContent::setTags(static::class, $this->id, [], $tags, $eraseExisting);
        // Clear cache since tags have changed
        $this->cachedTags = null;
        return $result;
    }
}
