<?php

namespace TN\TN_CMS\Controller;

use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Controller\Controller;

class TinyMCECommentController extends Controller
{
    #[Path('tinymce/comment/thread/get')]
    #[Component(\TN\TN_CMS\Component\TinyMCEComment\Thread\Get::class)]
    #[RoleOnly('content-editor')]
    public function getThread(): void {}

    #[Path('tinymce/comment/thread/create')]
    #[Component(\TN\TN_CMS\Component\TinyMCEComment\Thread\Create::class)]
    #[RoleOnly('content-editor')]
    public function createThread(): void {}

    #[Path('tinymce/comment/thread/delete')]
    #[Component(\TN\TN_CMS\Component\TinyMCEComment\Thread\Delete::class)]
    #[RoleOnly('content-editor')]
    public function deleteThread(): void {}

    #[Path('tinymce/comment/create')]
    #[Component(\TN\TN_CMS\Component\TinyMCEComment\Comment\Create::class)]
    #[RoleOnly('content-editor')]
    public function createComment(): void {}

    #[Path('tinymce/comment/edit')]
    #[Component(\TN\TN_CMS\Component\TinyMCEComment\Comment\Edit::class)]
    #[RoleOnly('content-editor')]
    public function editComment(): void {}

    #[Path('tinymce/comment/delete')]
    #[Component(\TN\TN_CMS\Component\TinyMCEComment\Comment\Delete::class)]
    #[RoleOnly('content-editor')]
    public function deleteComment(): void {}
}