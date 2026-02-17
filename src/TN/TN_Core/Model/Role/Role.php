<?php

namespace TN\TN_Core\Model\Role;

use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;

/**
 * a role on the system, belong to 0-n users. This can be used to control what users have permissions to do.
 *
 * for example staff writers are able to upload relevant content to site because they are assigned the
 * ‘Stats-projector’ role.
 *
 * Roles can be associated with any class that uses the RoleOwner trait, but that is typically only Users.
 *
 * @see \TN\Traits\RoleOwner
 * @see \TN\Users\Model\Users\Users
 *
 */
abstract class Role
{
    use \TN\TN_Core\Trait\Getter;
    use \TN\TN_Core\Trait\ExtendedSingletons;
    use ReadOnlyProperties;

    /**
     * Additional module namespaces to scan for Role subclasses (e.g. app-specific roles).
     * Set from application bootstrap so Role::getInstances() discovers them.
     * @var string[]
     */
    public static array $additionalExtendedNamespaces = [];

    /** @var string the role's key. Used for storage in the DB and reference in $access arrays on routes */
    public string $key;

    /** @var string the human-readable title of this role */
    public string $readable;

    /** @var string the human-readable description of this role */
    public string $description;

    /** @var string|null key associated with the role's role-group ?string because Role Groups may or may not have this property */
    public ?string $roleGroup = null;

    /** @return string[] module namespaces to scan for additional Role subclasses */
    public static function getAdditionalExtendedNamespaces(): array
    {
        return self::$additionalExtendedNamespaces;
    }

    /**
     * fires when a role owner is added to this role
     * @param mixed $roleOwner
     * @return void
     */
    public function roleOwnerAdded(mixed $roleOwner): void
    {

    }

    /**
     * fires when a role owner is removed from this role
     * @param mixed $roleOwner
     * @return void
     */
    public function roleOwnerRemoved(mixed $roleOwner): void
    {

    }

    /** @return string to support array_unique */
    public function __toString(): string
    {
        return $this->key;
    }

    public function isInRoleGroup(string $roleGroupKey): bool
    {
        $role = $this;
        while ($role instanceof Role) {
            if ($role->roleGroup === $roleGroupKey) {
                return true;
            }
            if ($role->roleGroup) {
                $role = Role::getInstanceByKey($role->roleGroup);
            } else {
                $role = null;
            }
        }
        return false;
    }

}