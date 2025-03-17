<?php

namespace TN\TN_Core\Model\Filesystem;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Directory
{
    public function __construct(public string $path)
    {
        if (!is_dir($this->path)) {
            throw new \InvalidArgumentException("Directory does not exist: $this->path");
        }
    }

    public function erase(bool $onlyContents = false): void
    {
        $it = new RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        if (!$onlyContents) {
            rmdir($this->path);
        }
    }
}