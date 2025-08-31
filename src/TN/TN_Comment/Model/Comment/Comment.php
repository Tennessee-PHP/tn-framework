<?php

namespace TN\TN_Comment\Model\Comment;

use DateTime;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\ForeignKey;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Search\SearchLimit;
use TN\TN_Comment\Model\Comment\CommentAttachment;
use TN\TN_Core\Model\User\User;

/**
 * Base comment model for user-generated comments on any content type
 * 
 * This model provides the core commenting functionality that can be extended
 * by specific implementations to add additional fields or behavior.
 */
#[TableName('comments_comments')]
class Comment implements Persistence
{
    use MySQL;
    use PersistentModel;

    // Core comment properties
    public string $contentType;
    public int $contentId;
    #[ForeignKey(User::class)] public int $userId;
    public string $content;
    public DateTime $createdAt;
    public DateTime $updatedAt;
    public ?int $parentId = null;

    // Relationships
    #[Impersistent] public User $user;
    #[Impersistent] public bool $userLoaded = false;
    #[Impersistent] public array $attachments = [];
    #[Impersistent] public bool $attachmentsLoaded = false;

    /**
     * Get the user who created this comment (lazy loading)
     * 
     * @return User User object
     */
    public function getUser(): User
    {
        if (!$this->userLoaded) {
            $this->user = User::readFromId($this->userId);
            $this->userLoaded = true;
        }
        return $this->user;
    }

    /**
     * Get attachments for this comment (lazy loading)
     * 
     * @return CommentAttachment[] Array of attachment objects
     */
    public function getAttachments(): array
    {
        if (!$this->attachmentsLoaded) {
            $this->attachments = CommentAttachment::getForComment($this->id);
            $this->attachmentsLoaded = true;
        }
        return $this->attachments;
    }

    /**
     * Check if this comment has attachments
     * 
     * @return bool True if comment has attachments
     */
    public function hasAttachments(): bool
    {
        return count($this->getAttachments()) > 0;
    }

    // =========================================================================
    // Static Methods for Content-Based Queries
    // =========================================================================

    /**
     * Get comments for specific content with pagination
     * 
     * @param string $contentType Full class name of the content
     * @param int $contentId ID of the content
     * @param int $start Starting offset (0-based)
     * @param int $num Number of comments to return
     * @return Comment[] Array of Comment objects
     */
    public static function getForContent(string $contentType, int $contentId, int $start = 0, int $num = 20): array
    {
        $searchArgs = new SearchArguments(
            conditions: [
                new SearchComparison('`contentType`', '=', $contentType),
                new SearchComparison('`contentId`', '=', $contentId),
                new SearchComparison('`parentId`', '=', 0)
            ],
            sorters: [
                new SearchSorter('createdAt', 'ASC')
            ],
            limit: new SearchLimit($start, $num)
        );
        
        return static::search($searchArgs);
    }

    /**
     * Get total comment count for specific content
     * 
     * @param string $contentType Full class name of the content
     * @param int $contentId ID of the content
     * @return int Total comment count
     */
    public static function getCountForContent(string $contentType, int $contentId): int
    {
        $searchArgs = new SearchArguments(
            conditions: [
                new SearchComparison('`contentType`', '=', $contentType),
                new SearchComparison('`contentId`', '=', $contentId),
                new SearchComparison('`parentId`', '=', 0)
            ]
        );
        
        return static::count($searchArgs);
    }
}
