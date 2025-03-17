<?php

namespace TN\TN_Core\Component\Email\ListEmailTemplates;

use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use \TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Model\Email\Template\Template;

#[Page('List of Email Templates')]
#[Route('TN_Core:Email:listEmailTemplates')]
class ListEmailTemplates extends HTMLComponent
{
    public array $templates;

    public function prepare(): void
    {
        $this->templates = Template::getInstances();
    }
}