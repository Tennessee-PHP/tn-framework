<?php

namespace TN\TN_Core\Trait;
use TN\TN_Core\Model\User\User;

/**
 * get related user
 * 
 */
trait GetUser
{
    /**
     * returns the user  belonging to the other object
     * @return ?User
     */
    public function getUser(): ?User
    {
        return User::readFromId($this->userId);
    }
}