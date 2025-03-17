<?php

namespace TN\TN_Core\Model\HashMethod;

class TNHashMethod extends HashMethod
{
    public string $key = 'tn';

    public function hash(string $password = '', string $salt = ''): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verify(string $password = '', string $hash = '', string $salt = ''): bool
    {
        return password_verify($password, $hash);
    }


    public function getHashData(string $password): array
    {
        return [
            'hash' => $this->hash($password)
        ];
    }
}