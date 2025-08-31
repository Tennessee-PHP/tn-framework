<?php

namespace TN\TN_Comment\Component\ListComments;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Comment\Interface\Commentable;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorterDirection;
use TN\TN_Core\Model\Package\Stack;

/**
 * Reusable comments list component for displaying paginated comments
 * Used by ViewScreenshot, ViewThread, and future forum pages
 */
class ListComments extends HTMLComponent
{
    public Commentable $contentModel;
    public array $comments = [];
    public Pagination $pagination;
    public int $itemsPerPage = 20;
    
    public function setContent(Commentable $model): void
    {
        $this->contentModel = $model;
    }
    
    public function prepare(): void
    {
        if (!isset($this->contentModel)) {
            return; // No content model set, show empty state
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
        
        // Load users and attachments for each comment
        foreach ($this->comments as $comment) {
            $comment->getUser(); // Load user data
            $comment->getAttachments(); // Load attachments
        }
    }
}
