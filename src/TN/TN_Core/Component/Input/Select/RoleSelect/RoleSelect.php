<?php

namespace TN\TN_Core\Component\Input\Select\RoleSelect;

use TN\TN_Core\Component\Input\Select\Option;
use TN\TN_Core\Component\Input\Select\Select;
use TN\TN_Core\Model\Role\Role;

/**
 * select a subscription plan
 *
 */
class RoleSelect extends Select
{
    public string $template = 'TN_Core/Component/Input/Select/Select.tpl';
    public string $requestKey = 'role';

    protected function getOptions(): array
    {
        $options = [];
        $options[] = new Option('', 'All', null, true);
        $roles = Role::getInstances();
        $roleReadables = [];
        foreach ($roles as $role) {
            $roleReadables[] = $role->readable;
        }
        array_multisort($roleReadables, SORT_ASC, $roles);
        unset($role);
        foreach ($roles as $role) {
            $options[] = new Option($role->key, $role->readable, $role);
        }
        return $options;
    }

    protected function getDefaultOption(): mixed
    {
        return $this->options[0];
    }
}