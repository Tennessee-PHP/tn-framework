<?php

namespace TN\TN_Comment\Component\ListComments;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Attribute\Components\Route;
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
#[Reloadable]
#[Route('TN_Comment:CommentsController:listComments')]
class ListComments extends HTMLComponent
{
    public Commentable $contentModel;
    public array $comments = [];
    public Pagination $pagination;
    public int $itemsPerPage = 20;
    
    public function prepare(): void
    {
        // If contentModel is not set, try to reconstruct it from reload data
        if (!isset($this->contentModel)) {
            $this->reconstructContentModel();
        }

        if (!isset($this->contentModel)) {
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

        // Load users and attachments for each comment
        foreach ($this->comments as $comment) {
            $comment->getUser(); // Load user data
            $comment->getAttachments(); // Load attachments
        }
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
        if (isset($this->contentModel)) {
            $data['contentType'] = get_class($this->contentModel);
            $data['contentId'] = $this->contentModel->id ?? null;
        }

        return $data;
    }
}
