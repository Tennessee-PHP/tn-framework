<?php

namespace TN\TN_Comment\Model\Comment;

use DateTime;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\MySQL\ForeignKey;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;

/**
 * Base comment attachment model for files attached to comments
 * 
 * This model provides the core attachment functionality that can be extended
 * by specific implementations to add additional fields or behavior.
 */
#[TableName('comments_attachments')]
abstract class CommentAttachment implements Persistence
{
    use MySQL;
    use PersistentModel;

    // Core attachment properties
    #[ForeignKey(Comment::class)] public int $commentId;
    public string $filename;
    public string $mimeType;
    public int $fileSize;
    public string $storageKey;
    public DateTime $uploadedAt;

    /**
     * Get attachments for a specific comment
     * 
     * @param int $commentId Comment ID
     * @return CommentAttachment[] Array of attachment objects
     */
    public static function getForComment(int $commentId): array
    {
        $searchArgs = new SearchArguments(
            conditions: [
                new SearchComparison('`commentId`', '=', $commentId)
            ]
        );
        
        return static::search($searchArgs);
    }

    /**
     * Get the download URL for this attachment
     * Must be implemented by extending classes to provide bucket-specific logic
     * 
     * @return string Download URL
     */
    abstract public function getDownloadUrl(): string;
}
