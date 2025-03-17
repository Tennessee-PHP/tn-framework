<?php

namespace TN\TN_Core\Error\Login;

enum LoginErrorMessage: string {
    case Inactive = 'This user is inactive';
    case InvalidCredentials = 'Those credentials didn\'t match up with any account';
    case Locked = 'This account is locked';
    case Timeout = 'Too many login attempts. Please try again later';
}