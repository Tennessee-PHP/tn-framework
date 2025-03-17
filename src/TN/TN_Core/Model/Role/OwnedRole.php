<?php

namespace TN\TN_Core\Model\Role;

use TN\TN_Core\Attribute\MySQL\ForeignKey;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\User\User;

/**
 * a record that a user owns a role
 */
#[TableName('users_owned_roles')]
class OwnedRole implements Persistence
{
    use MySQL;
    use PersistentModel;

    #[ForeignKey(User::class)]
    public int $userId;
    public string $roleKey;
}