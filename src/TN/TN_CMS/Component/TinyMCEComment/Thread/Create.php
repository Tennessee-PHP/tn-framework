<?php

namespace TN\TN_CMS\Component\TinyMCEComment\Thread;

use TN\TN_CMS\Model\TinyMCEComment\Thread;
use TN\TN_Core\Component\Renderer\Text\Text;

class Create extends Text {
    public function prepare(): void
    {
        $content = $_POST['content'];
        $ts = strtotime($_POST['createdAt']);
        $thread = Thread::createNew();
        $thread->addComment($content, $ts);
        $this->text = $thread->id;
    }
}