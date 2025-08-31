<?php

namespace TN\TN_Comment\Model\PersistentModel\Trait;

use DateTime;
use TN\TN_Comment\Model\Comment\Comment;
use TN\TN_Core\Model\Package\Stack;

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
            $commentClass = Comment::class;
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
            $commentClass = Comment::class;
        }
        return $commentClass::getCountForContent(static::class, $this->id);
    }

    /**
     * Add a new comment to this model
     * 
     * @param int $userId User ID of the commenter
     * @param string $content Comment content (HTML allowed)
     * @param int|null $parentId Parent comment ID for replies (null for top-level)
     * @return Comment Created comment object
     */
    public function addComment(int $userId, string $content, ?int $parentId = null): Comment
    {
        $commentClass = Stack::resolveClassName(Comment::class);
        if ($commentClass === false) {
            $commentClass = Comment::class;
        }
        $comment = $commentClass::getInstance();
        $comment->update([
            'contentType' => static::class,
            'contentId' => $this->id,
            'userId' => $userId,
            'content' => $content,
            'parentId' => $parentId,
            'createdAt' => new DateTime(),
            'updatedAt' => new DateTime()
        ]);
        
        return $comment;
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
