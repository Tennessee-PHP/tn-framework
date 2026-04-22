<?php

namespace TN\TN_Core\Model\Email\Template\Ne\Messaging;

/**
 * Email for a new direct message.
 *
 * **Template key:** `ne/messaging/dm` — use with `Email::sendFromTemplate('ne/messaging/dm', $to, $data)`.
 *
 * **$data parameters** (in addition to `to`, merged by `Email::sendFromTemplate`):
 * - `senderDisplayName` (string) — Display name of the person who sent the DM.
 * - `messagePreview` (string) — First lines of the message, plain text.
 * - `conversationUrl` (string) — Absolute URL (https) to the message thread in the app.
 * - `preferencesUrl` (string) — Same pattern as the in-app template: `FRONTEND_URL` + `/user/{username}/edit` for the **recipient**.
 */
class DirectMessage extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'ne/messaging/dm';
    protected string $name = 'New Element — new direct message';
    protected string $subject = 'New direct message from {$senderDisplayName} on {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Ne/Messaging/DirectMessage.tpl';
    protected array $sampleData = [
        'senderDisplayName' => 'CoasterFan92',
        'messagePreview' => "Hey! Did you get a chance to look at the layout edits I sent over?\n\n— CF92",
        'conversationUrl' => 'https://newelement.local/messages/99',
        'preferencesUrl' => 'https://newelement.local/user/YouNameHere/edit',
    ];
}
