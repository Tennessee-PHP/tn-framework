<?php

namespace TN\TN_Core\Controller;

use TN\TN_Core\Attribute\Command\CLI;
use TN\TN_Core\Attribute\Command\CommandName;
use TN\TN_Core\Attribute\Command\Schedule;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;

class EmailController extends Controller
{
    #[Path('staff/emails/list')]
    #[Component(\TN\TN_Core\Component\Email\ListEmailTemplates\ListEmailTemplates::class)]
    #[RoleOnly('email-template-editor')]
    public function listEmailTemplates(): void {}

    #[Path('staff/emails/edit/:key')]
    #[Component(\TN\TN_Core\Component\Email\EditEmailTemplate\EditEmailTemplate::class)]
    #[RoleOnly('email-template-editor')]
    public function editEmailTemplate(): void {}

    #[Path('staff/emails/save')]
    #[Component(\TN\TN_Core\Component\Email\EditEmailTemplate\SaveEmailTemplate::class)]
    #[RoleOnly('email-template-editor')]
    public function saveEmailTemplate(): void {}

    #[CommandName('convertkit/send-from-queue')]
    #[Schedule('*/5 * * * * *')]
    #[Component(\TN\TN_Core\CLI\Email\ConvertKit\SendFromQueue::class)]
    public function convertKitSendFromQueue(): void {}
}