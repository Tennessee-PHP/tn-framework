<?php

namespace TN\TN_Comment\Model\PersistentModel\Trait;

use DateTime;
use TN\TN_Comment\Model\Comment\Comment;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Attribute\NoCacheInvalidation;

/**
 * Trait to make any model "commentable"
 * Provides methods to manage comments associated with the model
 *
 * Models using this trait should implement the Commentable interface:
 * class MyModel implements Persistence, CommentableInterface {
 *     use PersistentModel, MySQL, Commentable;
 * }
 */
trait Commentable
{
    // Comment-related properties (updated by migration and comment operations)
    #[NoCacheInvalidation]
    public ?DateTime $lastComment = null;

    #[NoCacheInvalidation]
    public int $numComments = 0;

    /**
     * Get comments for this model
     * 
     * @param int $start Starting offset (0-based)
     * @param int $num Number of comments to return
     * @return Comment[] Array of Comment objects
     */
    public function getComments(int $start = 0, int $num = 20): array
    {
        $commentClass = Stack::resolveClassName(Comment::class);
        if ($commentClass === false) {
            // Try NE-specific Comment class first, fallback to framework
            $commentClass = class_exists('\NE\TN_Comment\Model\Comment\Comment')
                ? '\NE\TN_Comment\Model\Comment\Comment'
                : Comment::class;
        }
        return $commentClass::getForContent(static::class, $this->id, $start, $num);
    }

    /**
     * Get total comment count for this model
     * 
     * @return int Total number of comments
     */
    public function getCommentCount(): int
    {
        $commentClass = Stack::resolveClassName(Comment::class);
        if ($commentClass === false) {
            // Try NE-specific Comment class first, fallback to framework
            $commentClass = class_exists('\NE\TN_Comment\Model\Comment\Comment')
                ? '\NE\TN_Comment\Model\Comment\Comment'
                : Comment::class;
        }
        return $commentClass::getCountForContent(static::class, $this->id);
    }

    /**
     * Add a new comment to this model
     *
     * @param int $userId User ID of the commenter
     * @param string $content Raw body; meaning depends on contentFormat
     * @param int|null $parentId Parent comment ID for replies (null for top-level)
     * @param string $contentFormat Persisted format (markdown|html); extra keys are ignored if the resolved Comment model has no such properties
     * @param array|null $mentions Optional mention metadata JSON; ignored if the model has no mentions column
     * @return Comment Created comment object
     */
    public function addComment(int $userId, string $content, ?int $parentId = null, string $contentFormat = 'markdown', ?array $mentions = null): Comment
    {
        $commentClass = Stack::resolveClassName(Comment::class);
        if ($commentClass === false) {
            // Try NE-specific Comment class first, fallback to framework
            $commentClass = class_exists('\NE\TN_Comment\Model\Comment\Comment')
                ? '\NE\TN_Comment\Model\Comment\Comment'
                : Comment::class;
        }
        $comment = $commentClass::getInstance();
        $commentDate = new DateTime();
        $row = [
            'contentType' => static::class,
            'contentId' => $this->id,
            'userId' => $userId,
            'content' => $content,
            'parentId' => $parentId,
            'createdAt' => $commentDate,
            'updatedAt' => $commentDate,
            'contentFormat' => $contentFormat,
        ];
        if ($mentions !== null) {
            $row['mentions'] = $mentions;
        }
        $comment->update($row);

        // Update comment-related properties on the parent model
        $this->updateCommentStats($commentDate);

        return $comment;
    }

    /**
     * Update comment statistics on the model
     *
     * @param DateTime $commentDate The date of the latest comment
     */
    protected function updateCommentStats(DateTime $commentDate): void
    {
        // Get current comment count
        $currentCount = $this->getCommentCount();

        // Update properties
        $this->numComments = $currentCount;
        $this->lastComment = $commentDate;

        // Persist: save() with no args does not write any columns (see PersistentModel save / MySQL saveUpdate).
        $this->save(['numComments', 'lastComment']);
    }

    /**
     * Get the topic ID for this content (used for comment organization)
     * 
     * @return string Topic identifier in format "ClassName:ID"
     */
    public function getTopicId(): string
    {
        return static::class . ':' . $this->id;
    }
}
