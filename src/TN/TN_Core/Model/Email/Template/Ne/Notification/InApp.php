<?php

namespace TN\TN_Core\Model\Email\Template\Ne\Notification;

/**
 * Email for a single in-app notification (reply, park update, mention, etc.).
 *
 * **Template key:** `ne/notification/in-app` — use with `Email::sendFromTemplate('ne/notification/in-app', $to, $data)`.
 *
 * **$data parameters** (in addition to `to`, merged by `Email::sendFromTemplate`):
 * - `actorDisplayName` (string) — Who performed the action.
 * - `actionSummary` (string) — One or two short sentences, plain text.
 * - `actionUrl` (string) — Absolute URL (https) to the in-app deep link.
 * - `preferencesUrl` (string) — Absolute **Edit profile** URL. Build in PHP as
 *   `rtrim((string)($_ENV['FRONTEND_URL'] ?? ''), '/') . '/user/' . rawurlencode($username) . '/edit'`
 *   to match the ne6-app route `user/:username/edit` in `app/routes.ts`.
 */
class InApp extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'ne/notification/in-app';
    protected string $name = 'New Element — in-app notification';
    protected string $subject = 'You have a new notification on {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Ne/Notification/InApp.tpl';
    protected array $sampleData = [
        'actorDisplayName' => 'Riverbend',
        'actionSummary' => 'Riverbend left a new comment on your park “Cedar Point — Magnum 205”.',
        'actionUrl' => 'https://newelement.local/park/cedar-point-magnum#comments',
        'preferencesUrl' => 'https://newelement.local/user/Riverbend/edit',
    ];
}
