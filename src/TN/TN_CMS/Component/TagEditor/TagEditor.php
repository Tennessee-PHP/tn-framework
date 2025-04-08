<?php

namespace TN\TN_CMS\Component\TagEditor;

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
        TaggedContent::batchErase($this->taggedContents);
        $this->taggedContents = [];
        foreach ($data as $tagData) {
            $text = $tagData['text'];
            $tag = Tag::getExactTag($text, true);
            $taggedContent = TaggedContent::getInstance();
            $taggedContent->update([
                'contentClass' => $this->contentClass,
                'contentId' => $this->contentId,
                'tagId' => $tag->id,
                'primary' => (bool)($tagData['primary'] ?? false)
            ]);
        }

        // always add the author's name as a tag
        if (property_exists($this->content, 'authorName')) {
            $tag = Tag::getExactTag($this->content->authorName, true);
            $taggedContent = TaggedContent::getInstance();
            $taggedContent->update([
                'contentClass' => $this->contentClass,
                'contentId' => $this->contentId,
                'tagId' => $tag->id,
                'primary' => (bool)($tagData['primary'] ?? false)
            ]);
        }
    }

    public function prepare(): void {}
}
