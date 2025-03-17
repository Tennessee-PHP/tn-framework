<?php

namespace TN\TN_Core\Model\HashMethod;

use TN\TN_Core\Trait\ExtendedSingletons;

/**
 * a method for hashing passwords
 */
abstract class HashMethod
{
    use ExtendedSingletons;

    /** @var string  */
    public string $key;

    /**
     * @return string
     */
    public function generateSalt(): string
    {
        $random = new \PragmaRX\Random\Random();
        return $random->size(5)->get();
    }


    /**
     * @param string $password
     * @return array
     */
    abstract public function getHashData(string $password): array;

    /**
     * @param string $password
     * @param string $salt
     * @return string
     */
    abstract public function hash(string $password = '', string $salt = ''): string;

    /**
     * @param string $password
     * @param string $hash
     * @param string $salt
     * @return bool
     */
    abstract public function verify(string $password = '', string $hash = '', string $salt = ''): bool;
}