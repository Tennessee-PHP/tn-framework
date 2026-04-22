<?php

namespace TN\TN_Core\Model\Email\Template\Ne\Job;

/**
 * Email when another user assigns a misc job to the recipient.
 *
 * **Template key:** `ne/job/assigned` — use with `Email::sendFromTemplate('ne/job/assigned', $to, $data)`.
 *
 * **$data parameters** (in addition to `to`, merged by `Email::sendFromTemplate`):
 * - `assignerDisplayName` (string)
 * - `jobTitle` (string)
 * - `dueLine` (string) — plain sentence, e.g. "Due: Apr 30, 2026" or empty
 * - `jobsUrl` (string) — absolute URL to the jobs list (e.g. …/jobs?filter=my)
 * - `preferencesUrl` (string) — recipient edit profile URL
 */
class Assigned extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'ne/job/assigned';
    protected string $name = 'New Element — job assigned to you';
    protected string $subject = '{$assignerDisplayName} assigned you a job on {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Ne/Job/Assigned.tpl';
    protected array $sampleData = [
        'assignerDisplayName' => 'AdminUser',
        'jobTitle' => 'Review queue: new misc task',
        'dueLine' => 'Due: Apr 30, 2026',
        'jobsUrl' => 'https://newelement.local/jobs?filter=my',
        'preferencesUrl' => 'https://newelement.local/user/YouNameHere/edit',
    ];
}
