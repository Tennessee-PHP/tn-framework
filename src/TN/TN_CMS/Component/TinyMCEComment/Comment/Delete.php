<?php

namespace TN\TN_CMS\Component\TinyMCEComment\Comment;

use TN\TN_CMS\Model\TinyMCEComment\Comment;
use TN\TN_CMS\Model\TinyMCEComment\Thread;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;

class Delete extends JSON {
    public string|int $commentId;
    public function prepare(): void
    {
        try {
            $comment = Comment::readFromId($this->commentId);
            if (!$comment) {
                throw new ValidationException('Comment no longer exists');
            }
            $comment->delete();
            $this->data = [
                'canDelete' => true
            ];
        } catch (ValidationException $e) {
            $this->data = [
                'canDelete' => false,
                'reason' => $e->getMessage()
            ];
        }
    }
}