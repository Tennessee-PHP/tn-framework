<?php

namespace TN\TN_CMS\Component\Article\Admin\EditArticle\ArticleTitleEditor;

use TN\TN_CMS\Model\Article;
use \TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Model\User\User;

class ArticleTitleEditor extends HTMLComponent
{
    public Article $article;
    public bool $canEditAuthor = false;
    public array $authorOptions = [];

    public function prepare(): void
    {
        $this->canEditAuthor = User::getActive()->hasRole('article-editor');
        $this->authorOptions = [];
        
        foreach (User::getUsersWithRole('article-author') as $user) {
            $userArray = [
                'id' => $user->id,
                'name' => $user->name,
                'avatarUrl' => 'staffer-bio-images/default.png',
                'lastName' => $user->last,
            ];
            $this->authorOptions[] = $userArray;
        }

        $lastNames = array_column($this->authorOptions, 'lastName');
        array_multisort($lastNames, SORT_ASC, $this->authorOptions);
    }
}