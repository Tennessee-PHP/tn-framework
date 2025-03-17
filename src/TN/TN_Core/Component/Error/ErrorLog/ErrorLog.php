<?php

namespace TN\TN_Core\Component\Error\ErrorLog;

use TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Error\LoggedError;

#[Page('Error Log', '', false)]
#[Route('TN_Core:Error:errorLog')]
#[Reloadable]
class ErrorLog extends HTMLComponent
{
    public array $errors;
    public Pagination $pagination;

    public function prepare(): void
    {
        $allErrors = LoggedError::getLog();
        $this->pagination = new Pagination([
            'itemCount' => count($allErrors),
            'itemsPerPage' => 50
        ]);
        $this->pagination->prepare();
        $this->errors = array_slice($allErrors, $this->pagination->queryStart, $this->pagination->itemsPerPage);
    }
}