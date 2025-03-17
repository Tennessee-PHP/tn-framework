<?php

namespace TN\TN_CMS\Model\TinyMCEComment;

use DateTime;
use DateTimeInterface;
use Exception;
use TN\TN_Core\Attribute\MySQL\AutoIncrement;
use TN\TN_Core\Attribute\MySQL\PrimaryKey;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\User\User;

/**
 * comment on a thread. Comment system originally written for tinymce usage.
 * 
 * @property-read string $usersName
 * @property-read string $usersAvatar
 */
#[TableName('cms_comments_comments')]
class Comment implements \TN\TN_Core\Interface\Persistence
{
    /**
     * traits
     */
    use MySQL;
    use PersistentModel;
    
    /** @var int parent thread id */
    public int $threadId;

    /** @var int user Id */
    public int $userId;

    /** @var int timestamp when comment was first made */
    public int $ts;

    /** @var int last modified timestamp */
    public int $lastModifiedTs = 0;

    /** @var string the text of the comment */
    public string $content;

    /**
     * @param int $threadId
     * @param int $ts
     * @param string $content
     * @return Comment
     * @throws ValidationException
     */
    public static function createNew(int $threadId, int $ts, string $content): Comment
    {
        $user = User::getActive();
        if (!$user->hasRole('article-author') && !$user->hasRole('article-editor')) {
            throw new ValidationException('Only article authors and article editors can post comments');
        }
        $comment = self::getInstance();
        $comment->update([
            'threadId' => $threadId,
            'userId' => $user->id,
            'ts' => $ts,
            'content' => $content
        ]);
        return $comment;
    }

    /**
     * @param int $ts
     * @param string $content
     * @return void
     * @throws ValidationException
     */
    public function edit(int $ts, string $content): void
    {
        $user = User::getActive();
        if ($user->id !== $this->id) {
            throw new ValidationException('Only the comment author can edit it');
        }
        $this->update([
            'lastModifiedTs' => $ts,
            'content' => $content
        ]);
    }

    /**
     * @return void
     * @throws ValidationException
     */
    public function delete(): void
    {
        $user = User::getActive();
        if ($user->id !== $this->id && !$user->hasRole('article-editor')) {
            throw new ValidationException('Only the comment author, or any article editor, can delete a comment');
        }
        $this->erase();
    }

    public function __get(string $prop): mixed
    {
        return match($prop) {
            'usersName' => $this->getUsersName(),
            'usersAvatar' => $this->getUsersAvatar(),
            default => (property_exists($this, $prop) && isset($this->$prop)) ? $this->$prop : null
        };
    }

    /**
     * @return string
     */
    protected function getUsersName(): string
    {
        $user = User::readFromId($this->userId);
        return $user->name;
    }

    protected function getUsersAvatar(): string
    {
        return '';
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getData(): array
    {
        return [
            'author' => $this->getUsersName(),
            'authorAvatar' => $this->getUsersAvatar(),
            'createdAt' => (new DateTime('@' . $this->ts))->format(DateTimeInterface::ATOM),
            'content' => $this->content,
            'modifiedAt' => (new DateTime('@' . max($this->lastModifiedTs, $this->ts)))->format(DateTimeInterface::ATOM),
            'uid' => (string)$this->id
        ];
    }
}