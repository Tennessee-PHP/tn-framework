<?php

namespace TN\TN_CMS\Model;

use TN\TN_Core\Model\Package\Stack;

/**
 * $article->author
 * a piece of content
 *
 */
abstract class Content
{
    /**
     * gets an array of fully qualified, non-abstract classes that extend this one
     * @return string[]
     */
    public static function getContentClasses(): array
    {
        return Stack::getChildClasses(Content::class);
    }

    /**
     * is this piece of content hosted externally by another entity?
     * @return bool
     */
    public function getExternal(): bool
    {
        return false;
    }

    /**
     * gets a single content item
     * @param int|string $id
     * @return Content|null
     */
    abstract public static function getContentItem(int|string $id): ?Content;

    /** @return string */
    public abstract function getTitle(): string;

    /** @return string */
    public abstract function getSubtitle(): string;

    /** @return string */
    public abstract function getDescription(): string;

    /** @return string */
    public abstract function getUrl(): string;

    public abstract function getEditUrl(): ?string;

    /** @return string */
    public abstract function getThumbnailSrc(): string;

    /** @return string */
    public abstract function getVThumbnailSrc(): string;

    /* @return int */
    abstract public function getTs(): int;

    /** @return bool */
    abstract public function isPublished(): bool;

    /** @return int|null */
    abstract public function getCreatorId(): ?int;

    /** @return int */
    abstract public function getWeight(): int;

    /** @return bool */
    abstract public function getAlwaysCurrent(): bool;

    /** @return string */
    abstract static public function getReadableContentType(): string;

    /** @return string[] */
    abstract static public function getImpersistentPageEntryFields(): array;

    /**
     * Get properties that should trigger PageEntry cache invalidation when changed
     * Content classes should return an array of property names that affect PageEntry fields
     * 
     * @return string[] Array of property names that trigger PageEntry updates
     */
    abstract protected function getPageEntryRelevantProperties(): array;

    /**
     * @param PageEntry $pageEntry
     * @return void
     */
    abstract public function updateFromPageEntry(PageEntry $pageEntry): void;

    /** @return void */
    public function erasePageEntry(): void
    {
        $pageEntry = PageEntry::getPageEntryForContentItem(get_class($this), $this->id);
        $pageEntry?->erase();
    }

    /**
     * Check if PageEntry should be updated based on changed properties
     * Only updates PageEntry if properties that affect PageEntry fields have changed
     * 
     * @param array $changedProperties Properties that have changed in the content object
     * @return bool True if PageEntry should be updated
     */
    protected function shouldUpdatePageEntry(array $changedProperties): bool
    {
        $pageEntryRelevantProperties = $this->getPageEntryRelevantProperties();
        $relevantChanges = array_intersect(array_keys($changedProperties), $pageEntryRelevantProperties);
        
        return !empty($relevantChanges);
    }

    /** @return void */
    public function writeToPageEntry(): void
    {
        $pageEntry = PageEntry::getPageEntryForContentItem(get_class($this), $this->id);
        if (!$this->isPublished()) {
            $pageEntry?->erase();
            return;
        }
        if (!$pageEntry) {
            $pageEntry = PageEntry::getInstance();
            $new = true;
        } else {
            $new = false;
        }

        $update = [
            'contentClass' => get_class($this),
            'contentId' => $this->id,
            'title' => $this->getTitle(),
            'subtitle' => $this->getSubtitle(),
            'description' => $this->getDescription(),
            'path' => $this->getUrl(),
            'thumbnailSrc' => $this->getThumbnailSrc(),
            'vThumbnailSrc' => $this->getVThumbnailSrc(),
            'ts' => $this->getTs(),
            'weight' => $this->getWeight(),
            'alwaysCurrent' => $this->getAlwaysCurrent(),
            'creatorId' => $this->getCreatorId()
        ];
        if ($new) {
            $impersistentPageEntryFields = $this->getImpersistentPageEntryFields();
            foreach ($impersistentPageEntryFields as $field) {
                unset($update[$field]);
            }
        }

        $pageEntry->updateFromContent = true;
        $pageEntry->update($update);
        $pageEntry->updateFromContent = false;
    }

    /**
     * Framework-level hook for Content classes to handle PageEntry updates intelligently
     * Content classes should call this from their afterSaveUpdate() method
     * 
     * @param array $changedProperties Properties that have changed
     */
    protected function handlePageEntryUpdate(array $changedProperties): void
    {
        if ($this->shouldUpdatePageEntry($changedProperties)) {
            $this->writeToPageEntry();
        }
    }
}
