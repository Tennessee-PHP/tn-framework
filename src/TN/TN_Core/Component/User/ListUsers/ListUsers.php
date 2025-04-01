<?php

namespace TN\TN_Core\Component\User\ListUsers;

use TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Component\User\ListUsers\ListUsersTable\ListUsersTable;
use TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;

#[Page('List Users')]
#[Route('TN_Core:Users:listUsers')]
class ListUsers extends HTMLComponent
{
    public ListUsersTable $listUsersTable;

    public function prepare(): void
    {
        $this->listUsersTable = new ListUsersTable();
        $this->listUsersTable->prepare();
    }
}
