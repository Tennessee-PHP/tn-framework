<?php

namespace TN\TN_CMS\Component\TinyMCEComment\Comment;

use TN\TN_CMS\Model\TinyMCEComment\Comment;
use TN\TN_CMS\Model\TinyMCEComment\Thread;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;

class Edit extends JSON {
    public string|int $commentId;
    public function prepare(): void
    {
        try {
            $content = $_POST['content'];
            $ts = strtotime($_POST['modifiedAt']);
            $comment = Comment::readFromId($this->commentId);
            if (!$comment) {
                throw new ValidationException('Comment no longer exists');
            }
            $comment->edit($ts, $content);
            $this->data = [
                'canEdit' => true
            ];
        } catch (ValidationException $e) {
            $this->data = [
                'canEdit' => false,
                'reason' => $e->getMessage()
            ];
        }
    }
}