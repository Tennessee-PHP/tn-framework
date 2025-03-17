<?php

namespace TN\TN_CMS\Model\TinyMCEComment;

use TN\TN_Core\Attribute\MySQL\AutoIncrement;
use TN\TN_Core\Attribute\MySQL\PrimaryKey;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\Relationships\ChildrenClass;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\User\User;

/**
 * a thread of comments. System originally written for usage within tinymce.
 *
 */
#[TableName('cms_comments_threads')]
class Thread implements Persistence
{
    /**
     * traits
     */
    use MySQL;
    use PersistentModel;

    #[ChildrenClass('TN\TN_CMS\Model\TinyMCEComment\Comment')]
    public array $comments = [];

    /**
     * @return Thread
     * @throws ValidationException
     */
    public static function createNew(): Thread
    {
        $user = User::getActive();
        if (!$user->hasRole('article-author') && !$user->hasRole('article-editor')) {
            throw new ValidationException('Only article authors and article editors can post comments');
        }
        $thread = self::getInstance();
        $thread->save();
        return $thread;
    }

    /**
     * @param string $content
     * @param int $ts
     * @return Comment
     * @throws ValidationException
     */
    public function addComment(string $content, int $ts): Comment
    {
        return Comment::createNew($this->id, $ts, $content);
    }

    /**
     * @return array[]
     * @throws ValidationException
     */
    public function getData(): array
    {
        $user = User::getActive();
        if (!$user->hasRole('article-author') && !$user->hasRole('article-editor')) {
            throw new ValidationException('Only article authors and article editors can view comments');
        }
        // read all the comments
        $this->comments = Comment::searchByProperty('threadId', $this->id);

        $commentsData = [];
        foreach ($this->comments as $comment) {
            $commentsData[] = $comment->getData();
        }

        // create object
        return [
            'conversation' => [
                'uid' => (string)$this->id,
                'comments' => $commentsData
            ]
        ];
    }

    /**
     * @return void
     * @throws ValidationException
     */
    public function delete(): void
    {
        $user = User::getActive();
        if (!$user->hasRole('article-editor')) {
            throw new ValidationException('Only article editors can delete comment threads');
        }
        $this->erase();
    }
}