<?php

namespace TN\TN_Comment\Interface;

use TN\TN_Comment\Model\Comment\Comment;

/**
 * Interface for models that support comments
 * Implemented by the Commentable trait
 */
interface Commentable
{
    /**
     * Get comments for this content with pagination
     * 
     * @param int $start Starting offset
     * @param int $num Number of comments to retrieve
     * @return Comment[] Array of comment objects
     */
    public function getComments(int $start = 0, int $num = 20): array;
    
    /**
     * Get total count of comments for this content
     * 
     * @return int Total number of comments
     */
    public function getCommentCount(): int;
    
    /**
     * Add a new comment to this content
     * 
     * @param int $userId User ID of the commenter
     * @param string $content Comment content (HTML)
     * @param int|null $parentId Optional parent comment ID for replies
     * @return Comment Created comment object
     */
    public function addComment(int $userId, string $content, ?int $parentId = null): Comment;
    
    /**
     * Get the topic ID for this content (used for comment organization)
     * 
     * @return string Topic identifier in format "ClassName:ID"
     */
    public function getTopicId(): string;
}
