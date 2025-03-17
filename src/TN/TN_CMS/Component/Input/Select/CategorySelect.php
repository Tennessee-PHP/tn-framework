<?php

namespace TN\TN_CMS\Component\Input\Select;

use TN\TN_CMS\Model\Category;
use TN\TN_Core\Component\Input\Select\Option;
use TN\TN_Core\Component\Input\Select\Select;

class CategorySelect extends Select {

    public string $requestKey = 'category';

    protected function getOptions(): array
    {
        $options = [];
        $options[] = new Option('', 'All Categories', null, true);
        foreach (Category::getAll() as $category) {
            $options[] = new Option($category->id, $category->text, $category);
        }
        return $options;
    }

    protected function getDefaultOption(): mixed
    {
        return $this->options[0];
    }
}