<?php

namespace TN\TN_CMS\Component\TinyMCEComment\Comment;

use TN\TN_CMS\Model\TinyMCEComment\Thread;
use TN\TN_Core\Component\Renderer\Text\Text;
use TN\TN_Core\Error\ValidationException;

class Create extends Text {
    public string|int $threadId;
    public function prepare(): void
    {
        $content = $_POST['content'];
        $ts = strtotime($_POST['createdAt']);
        $thread = Thread::readFromId($this->threadId);
        if (!$thread) {
            throw new ValidationException('Comment thread no longer exists');
        }
        $comment = $thread->addComment($content, $ts);
        $this->text = $comment->id;
    }
}