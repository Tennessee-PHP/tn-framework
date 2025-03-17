<?php

namespace TN\TN_Core\Model\User;
use TN\TN_Core\Attribute\Constraints\Strlen;

#[Strlen(9)]
enum PasswordResetType: string {
    case Reset = 'reset';
    case Register = 'register';
}
