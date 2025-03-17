<?php

namespace TN\TN_Core\Component\User\UserProfile;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Model\User\User;

abstract class UserProfileTab extends HTMLComponent {
    public static string $tabKey;
    public static string $tabReadable;
    public static int $sortOrder = 100;

    public User $user;
    public string $username;

    public static function enabled(User $user): bool {
        return true;
    }
}