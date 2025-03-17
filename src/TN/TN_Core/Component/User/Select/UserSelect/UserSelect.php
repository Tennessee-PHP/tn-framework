<?php

namespace TN\TN_Core\Component\User\Select\UserSelect;

use TN\TN_Core\Attribute\Components\FromRequest;
use \TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Model\User\User;

class UserSelect extends HTMLComponent
{
    public array $users = [];
    public array $options;
    public ?User $selected = null;
    public string $displayProperty = 'username';
    public string $requestKey = 'userId';
    public string $allLabel = 'Select a user...';
    #[FromRequest] public int $userId = 0;

    public function prepare(): void
    {
        $this->options = $this->users;
        $sortValues = [];
        $displayProperty = $this->displayProperty;
        foreach ($this->users as $user) {
            $sortValues[] = $user->$displayProperty;
        }
        array_multisort($sortValues, SORT_ASC, $this->options);
        foreach ($this->options as $user) {
            if ($user->id === $this->userId) {
                $this->selected = $user;
                break;
            }
        }
    }


}