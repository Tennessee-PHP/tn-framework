<?php

namespace TN\TN_Core\Component;

use TN\TN_CMS\Model\PageEntry;
use TN\TN_Core\Component\Title\Title;

interface PageComponent {
    public function getPageTitle(): string;
    public function getPageSubtitle(): ?string;
    public function getBreadcrumbEntries(): array;
    public function getPageTitleComponent(array $options): ?Title;
    public function getPageRoute(): string;
    public function getPageDescription(): string;
    public function getPageOpenGraphImage(): ?string;
    public function getContentPageEntry(): ?PageEntry;
    public function getPageEntryTags(): array;
    public function prepare(): void;
    public function getPageJsVars(): array;
    public function render(): string;
    public function getPageIndexKey(): string;
    public function getPageIndexPath(): string;
    public function getPageIndex(): bool;
}