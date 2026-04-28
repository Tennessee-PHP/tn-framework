<?php

namespace TN\TN_CMS\Component\TagEditor;

use TN\TN_CMS\Model\PageEntry;
use TN\TN_CMS\Model\Tag\Tag;
use TN\TN_CMS\Model\Tag\TaggedContent;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Component\Renderer\TemplateRender;
use TN\TN_Core\Error\ValidationException;

/**
 * tags editor
 * 
 *
 */
class TagEditor extends HTMLComponent
{
    public array $taggedContents;
    public string $contentClass;
    public null|int|string $contentId;
    public mixed $content;

    /**
     * @param mixed $content
     */
    public function __construct(mixed $content)
    {
        $this->content = $content;
        $contentClass = get_class($this->content);
        $contentId = $this->content->id ?? null;
        if ($contentId) {
            $this->taggedContents = TaggedContent::getFromContentItem($contentClass, $contentId);
        } else {
            $this->taggedContents = [];
        }
        $this->contentClass = $contentClass;
        $this->contentId = $contentId;
        parent::__construct();
    }

    /**
     * updates the tagged content items based on the provided tag data
     * @param $data
     * @return void
     * @throws ValidationException
     */
    public function updateTags($data): void
    {
        if ($this->contentId == null) {
            throw new ValidationException('Cannot save tags with a contentId of null');
        }

        $finalTagData = $this->getTagDataWithAuthor($data);
        if ($this->tagDataMatchesExistingTags($finalTagData)) {
            return;
        }

        TaggedContent::batchErase($this->taggedContents);
        $this->taggedContents = [];
        foreach ($finalTagData as $tagData) {
            $text = $tagData['text'];
            $tag = Tag::getExactTag($text, true);
            $taggedContent = TaggedContent::getInstance();
            $taggedContent->update([
                'contentClass' => $this->contentClass,
                'contentId' => $this->contentId,
                'tagId' => $tag->id,
                'primary' => (bool)($tagData['primary'] ?? false)
            ]);
            $this->taggedContents[] = $taggedContent;
        }

        // we can now update numTags on the page entry
        $pageEntry = PageEntry::getPageEntryForContentItem(get_class($this->content), $this->content->id);
        $pageEntry?->update([
            'numTags' => count($this->taggedContents)
        ]);
    }

    /**
     * @param mixed $data
     * @return array<int, array{text: string, primary: bool}>
     */
    protected function getTagDataWithAuthor(mixed $data): array
    {
        $tagData = is_array($data) ? array_values($data) : [];
        $lastPrimary = false;
        foreach ($tagData as $index => $tag) {
            $tagData[$index] = [
                'text' => (string)($tag['text'] ?? ''),
                'primary' => (bool)($tag['primary'] ?? false)
            ];
            $lastPrimary = $tagData[$index]['primary'];
        }

        // Always add the author's name as a tag, preserving the existing primary behavior.
        if (property_exists($this->content, 'authorName')) {
            $tagData[] = [
                'text' => (string)$this->content->authorName,
                'primary' => $lastPrimary
            ];
        }

        return $tagData;
    }

    /**
     * @param array<int, array{text: string, primary: bool}> $tagData
     * @return bool
     */
    protected function tagDataMatchesExistingTags(array $tagData): bool
    {
        if (count($this->taggedContents) !== count($tagData)) {
            return false;
        }

        $existing = [];
        foreach ($this->taggedContents as $taggedContent) {
            if (!isset($taggedContent->tag)) {
                return false;
            }
            $existing[] = $this->tagComparisonKey($taggedContent->tag->text, $taggedContent->primary);
        }

        $requested = [];
        foreach ($tagData as $tag) {
            $requested[] = $this->tagComparisonKey($tag['text'], $tag['primary']);
        }

        sort($existing);
        sort($requested);
        return $existing === $requested;
    }

    protected function tagComparisonKey(string $text, bool $primary): string
    {
        return strtolower($text) . '|' . ($primary ? '1' : '0');
    }

    public function prepare(): void {}
}
