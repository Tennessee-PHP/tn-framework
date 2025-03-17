<?php

namespace TN\TN_CMS\Component\PageEntry\Admin\EditPageEntry;

use TN\TN_Core\Component\Renderer\JSON\JSON;

class SavePageEntry extends JSON {
    public function prepare(): void
    {
        $editPageEntryForm = new EditPageEntry(['pageEntryId' => (int)$_POST['pageEntryId']]);
        $editPageEntryForm->prepare();
        $editPageEntryForm->updateRecord(
            $_POST['title'] ?? '',
            $_POST['subtitle'] ?? '',
            $_POST['description'] ?? '',
            (int)$_POST['weight'],
            $_POST['thumbnailSrc'] ?? '',
            $_POST['vThumbnailSrc'] ?? '',
            $_POST['tags']
        );
        $this->data = [
            'title' => $editPageEntryForm->pageEntry->title,
            'subtitle' => $editPageEntryForm->pageEntry->subtitle,
            'description' => $editPageEntryForm->pageEntry->description
        ];
    }
}