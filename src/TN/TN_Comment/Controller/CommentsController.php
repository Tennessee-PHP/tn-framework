<?php

namespace TN\TN_Comment\Controller;

use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Access\Restrictions\Anyone;
use TN\TN_Core\Attribute\Route\Component;

/**
 * Comments controller for handling comment-related routes
 * Provides reload routes for the ListComments component
 */
class CommentsController extends Controller
{
    /**
     * Main comments route - handles full page loads
     * This route is used when comments are loaded as part of a full page
     */
    #[Path('comments/list')]
    #[Anyone]
    #[Component(\TN\TN_Comment\Component\ListComments\ListComments::class)]
    public function listComments(): void {}
}
