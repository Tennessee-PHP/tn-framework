<?php

namespace TN\TN_Core\Model\HashMethod;

class IBFHashMethod extends HashMethod
{
    public string $key = 'ibf';

    public function hash(string $password = '', string $salt = ''): string
    {
        return md5(md5($salt) . md5($password));
    }

    public function verify(string $password = '', string $hash = '', string $salt = ''): bool
    {
        return $this->hash($password, $salt) === $hash;
    }

    public function getHashData(string $password): array
    {
        $salt = $this->generateSalt();
        return [
            'salt' => $salt,
            'hash' => $this->hash($password, $salt)
        ];
    }
}