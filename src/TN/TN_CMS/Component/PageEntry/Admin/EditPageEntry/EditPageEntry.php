<?php

namespace TN\TN_CMS\Component\PageEntry\Admin\EditPageEntry;

use TN\TN_CMS\Component\TagEditor\TagEditor;
use TN\TN_CMS\Model\PageEntry;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Error\ValidationException;

#[Reloadable]
class EditPageEntry extends HTMLComponent
{
    public ?PageEntry $pageEntry = null;
    public TagEditor $tagEditor;
    public ?int $pageEntryId = null;
    public ?string $editContentText = null;
    public ?string $editContentUrl = null;

    public function prepare(): void
    {
        if (!isset($this->pageEntryId)) {
            $this->pageEntryId = (int)$_GET['pageEntryId'];
        }
        if (!$this->pageEntryId) {
            return;
        }
            $this->pageEntry = PageEntry::readFromId($this->pageEntryId);
            $this->tagEditor = new TagEditor($this->pageEntry->getContent());
            $this->tagEditor->prepare();

        $content = $this->pageEntry->getContent();
        if (!$content || $content instanceof PageEntry) {
            return;
        }
        $this->editContentText = 'Edit ' . $content::getReadableContentType();
        $this->editContentUrl = $content->getEditUrl();

    }

    public function updateRecord(string $title, string $subtitle, string $description, int $weight, string $thumbnailSrc, string $vThumbnailSrc, string $tagsJson): void
    {
        $this->pageEntry->update([
            'title' => htmlentities(trim($title)),
            'subtitle' => htmlentities(trim($subtitle)),
            'description' =>  htmlentities(trim($description)),
            'thumbnailSrc' => trim($thumbnailSrc),
            'vThumbnailSrc' => trim($vThumbnailSrc),
            'weight' => min(10, max($weight, 0))
        ]);
        $this->tagEditor->updateTags(json_decode($tagsJson, true));
    }
}