<?php

namespace TN\TN_CMS\CLI\PageEntry;

use TN\TN_CMS\Model\Content;
use TN\TN_Core\CLI\CLI;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchLimit;

class AddPageEntriesForContentItems extends CLI
{
    public function run(): void
    {
        // let's get all the classes that extend Content
        foreach (Stack::getChildClasses(Content::class) as $class) {
            $this->addPageEntriesForClass($class);
        }
    }

    public function addPageEntriesForClass(string $class): void
    {
        if ($class::getReadableContentType() !== 'Article') {
            return;
        }
        $this->out('Adding page entries for: ' . $class::getReadableContentType() . ', class: ' . $class);
        $search = new SearchArguments();
        $count = $class::count($search);
        $this->out($count);
        $progress = $this->progress()->total($count);
        $numPerPage = 100;
        $page = 1;
        $numPages = ceil($count / $numPerPage);

        while ($page <= $numPages) {
            $search->limit = new SearchLimit(($page - 1) * $numPerPage, $numPerPage);
            foreach ($class::search($search) as $content) {
                $content->writeToPageEntry();
                try {
                    $progress->advance(1);
                } catch (\Exception $e) {
                    continue;
                }
            }

            $page += 1;
        }
    }
}