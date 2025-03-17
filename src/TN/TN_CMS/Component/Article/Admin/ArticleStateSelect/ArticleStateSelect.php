<?php

namespace TN\TN_CMS\Component\Article\Admin\ArticleStateSelect;

use TN\TN_CMS\Model\Article;
use TN\TN_Core\Component\Input\Select\Option;
use TN\TN_Core\Component\Input\Select\Select;

class ArticleStateSelect extends Select
{
    public string $requestKey = 'articlestate';

    protected function getOptions(): array
    {
        $options = [];
        $options[] = new Option('', 'All States', null, true);
        foreach (Article::getAllStates() as $value => $state) {
            $options[] = new Option($value, $state, null);
        }
        return $options;
    }

    protected function getDefaultOption(): mixed
    {
        return $this->options[0];
    }
}