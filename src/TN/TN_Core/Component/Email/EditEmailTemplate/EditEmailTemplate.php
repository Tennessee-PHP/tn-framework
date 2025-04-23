<?php

namespace TN\TN_Core\Component\Email\EditEmailTemplate;

use TN\TN_Core\Attribute\Components\HTMLComponent\RequiresTinyMCE;
use TN\TN_Core\Component\Email\ListEmailTemplates\ListEmailTemplates;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use \TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Model\Email\CustomTemplate;
use TN\TN_Core\Model\Email\Template\Template;
use TN\TN_Core\Model\Package\Package;

#[Page('Edit Email Template')]
#[Route('TN_Core:Email:editEmailTemplate')]
#[RequiresTinyMCE]
#[Breadcrumb(ListEmailTemplates::class)]
class EditEmailTemplate extends HTMLComponent
{
    public string $key;
    public mixed $emailTemplate;
    public ?string $content;
    public string $name;
    public array $sampleData;

    public function prepare(): void
    {
        $key = str_replace('-', '/', $this->key);
        $name = Template::getInstanceByKey($key)->name;
        if (CustomTemplate::getFromKey($key)) {
            $template = CustomTemplate::getFromKey($key);
            $content = $template->template;
        } else {
            $template = Template::getInstanceByKey($key);
            foreach (Package::getAll() as $package) {
                $content = file_get_contents($package->getDir() . $template->defaultTemplateFile);
                if ($content) {
                    break;
                }
            }
        }
        $sampleData = Template::getInstanceByKey($key)->sampleData;
        $this->emailTemplate = $template;
        $this->content = $content;
        $this->name = $name ?? '';
        $this->sampleData = $sampleData;
    }
}
