<?php

namespace TN\TN_Comment\Component\ListComments;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Comment\Interface\Commentable;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorterDirection;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\User\User;
use TN\TN_Comment\Model\Comment\CommentAttachment;

/**
 * Reusable comments list component for displaying paginated comments
 * Used by ViewScreenshot, ViewThread, and future forum pages
 */
#[Reloadable]
#[Route('TN_Comment:CommentsController:listComments')]
class ListComments extends HTMLComponent
{
    public ?Commentable $contentModel = null;
    public array $comments = [];
    public Pagination $pagination;
    public int $itemsPerPage = 20;
    
    public function prepare(): void
    {
        // If contentModel is not set, try to reconstruct it from reload data
        if ($this->contentModel === null) {
            $this->reconstructContentModel();
        }

        if ($this->contentModel === null) {
            return; // No content model available, show empty state
        }

        // Create search arguments for comments
        $search = new SearchArguments();
        $search->sorters = [new SearchSorter('createdAt', SearchSorterDirection::ASC)]; // Oldest first

        // Get total comment count
        $totalComments = $this->contentModel->getCommentCount();

        // Set up pagination
        $this->pagination = new (Stack::resolveClassName(Pagination::class))([
            'itemCount' => $totalComments,
            'itemsPerPage' => $this->itemsPerPage,
            'search' => $search
        ]);
        $this->pagination->prepare();

        // Load comments using pagination
        $this->comments = $this->contentModel->getComments($this->pagination->queryStart, $this->pagination->itemsPerPage);

        // Bulk load users and attachments for all comments to avoid N+1 queries
        $this->bulkLoadCommentUsers($this->comments);
        $this->bulkLoadCommentAttachments($this->comments);
    }

    /**
     * Reconstruct content model from reload data
     */
    private function reconstructContentModel(): void
    {
        $request = \TN\TN_Core\Model\Request\HTTPRequest::get();

        // Try to get content type and ID from request parameters
        $contentType = $request->getRequest('contentType');
        $contentId = $request->getRequest('contentId');

        if ($contentType && $contentId) {
            // Resolve the class and load the model
            $contentClass = Stack::resolveClassName($contentType);
            if ($contentClass && method_exists($contentClass, 'readFromId')) {
                $this->contentModel = $contentClass::readFromId($contentId);
            }
        }
    }

    /**
     * Get template data for AJAX reloads
     */
    public function getTemplateData(): array
    {
        $data = parent::getTemplateData();

        // Add content model information for AJAX reloads
        if ($this->contentModel !== null) {
            $data['contentType'] = get_class($this->contentModel);
            $data['contentId'] = $this->contentModel->id ?? null;
        }

        return $data;
    }

    /**
     * Bulk load users for multiple comments to avoid N+1 queries
     * 
     * @param array $comments Array of Comment objects to load users for
     */
    private function bulkLoadCommentUsers(array $comments): void
    {
        if (empty($comments)) {
            return;
        }

        // Extract unique user IDs
        $userIds = array_unique(array_map(fn($comment) => $comment->userId, $comments));
        
        if (empty($userIds)) {
            return;
        }

        // Bulk load all User objects
        $users = User::search(new SearchArguments(
            conditions: [
                new SearchComparison('`id`', 'IN', $userIds)
            ]
        ));
        
        // Create user lookup map
        $userLookup = [];
        foreach ($users as $user) {
            $userLookup[$user->id] = $user;
        }
        
        // Assign users to comments
        foreach ($comments as $comment) {
            if (isset($userLookup[$comment->userId])) {
                $comment->user = $userLookup[$comment->userId];
                $comment->userLoaded = true;
            }
        }
    }

    /**
     * Bulk load attachments for multiple comments to avoid N+1 queries
     * 
     * @param array $comments Array of Comment objects to load attachments for
     */
    private function bulkLoadCommentAttachments(array $comments): void
    {
        if (empty($comments)) {
            return;
        }

        // Extract comment IDs
        $commentIds = array_map(fn($comment) => $comment->id, $comments);
        
        if (empty($commentIds)) {
            return;
        }

        // Bulk load all CommentAttachment objects
        $attachments = CommentAttachment::search(new SearchArguments(
            conditions: [
                new SearchComparison('`commentId`', 'IN', $commentIds)
            ]
        ));
        
        // Group attachments by comment ID
        $attachmentMap = [];
        foreach ($attachments as $attachment) {
            $attachmentMap[$attachment->commentId][] = $attachment;
        }
        
        // Assign attachments to comments
        foreach ($comments as $comment) {
            $comment->attachments = $attachmentMap[$comment->id] ?? [];
            $comment->attachmentsLoaded = true;
        }
    }
}
