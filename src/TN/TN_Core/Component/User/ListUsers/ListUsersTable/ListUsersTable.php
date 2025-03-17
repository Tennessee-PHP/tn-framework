<?php

namespace TN\TN_Core\Component\User\ListUsers\ListUsersTable;

use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Component\Input\Select\RoleSelect\RoleSelect;
use TN\TN_Core\Component\Pagination\Pagination;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonArgument;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonJoin;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorterDirection;
use TN\TN_Core\Model\Role\OwnedRole;
use TN\TN_Core\Model\User\User;

#[Reloadable('TN_Core:User:listUsersTableReload')]
class ListUsersTable extends HTMLComponent
{
    public Pagination $pagination;
    public RoleSelect $roleSelect;
    #[FromQuery] public ?string $username = null;
    #[FromQuery] public ?string $email = null;

    /** @var User[]  */
    public array $users;

    public function prepare(): void
    {
        $this->roleSelect = new RoleSelect();
        $this->roleSelect->prepare();

        $search = new SearchArguments();
        $search->sorters[] = new SearchSorter('id', SearchSorterDirection::DESC);

        if (!$this->roleSelect->selected->all && $this->roleSelect->selected->key) {
            $search->conditions[] = new SearchComparisonJoin(joinFromClass: OwnedRole::class, joinToClass: User::class);
            $search->conditions[] = new SearchComparison(new SearchComparisonArgument(property: 'roleKey', class: OwnedRole::class), '=', $this->roleSelect->selected->key);
        }

        if (!empty($this->username)) {
            $search->conditions[] = new SearchComparison('`username`', 'LIKE', "%{$this->username}%");
        }

        if (!empty($this->email)) {
            $search->conditions[] = new SearchComparison('`email`', 'LIKE', "%{$this->email}%");
        }

        $count = User::count($search);
        $this->pagination = new Pagination([
            'itemCount' => $count,
            'itemsPerPage' => 50,
            'search' => $search
        ]);
        $this->pagination->prepare();

        $this->users = User::search($search);
    }
}