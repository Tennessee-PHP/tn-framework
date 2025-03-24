<?php

namespace TN\TN_Core\Model\Email\Template\User;

class Locked extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'user/locked';
    protected string $name = 'Locked Account';
    protected string $subject = 'Locked Account at {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Users/Locked.tpl';
    protected array $sampleData = [
        'username' => 'someUserName',
        'ipLimit' => 'ipLimit',
        'hours' => 'hoursLockedOut'
    ];
}
