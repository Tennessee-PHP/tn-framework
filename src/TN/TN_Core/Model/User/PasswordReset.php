<?php

namespace TN\TN_Core\Model\User;

use TN\TN_Core\Attribute\Constraints\Strlen;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\Login\LoginErrorMessage;
use TN\TN_Core\Error\Login\ResetPasswordTimeoutException;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\Email\Email;
use TN\TN_Core\Model\IP\IP;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Time\Time;

/**
 * a request to reset a password
 */
#[TableName('users_password_resets')]
class PasswordReset implements Persistence
{
    use MySQL;
    use PersistentModel;

    const int EXPIRES_AFTER = 86400;

    /** @var int how many login attempts are allowed before lock-out */
    const int PASSWORD_RESET_REQUESTS_ALLOWED = 5;

    /** @var int how long to wait when locked out */
    const int PASSWORD_RESET_REQUEST_TIMEOUT = 600;

    public int $userId;
    public string $key;
    public int $startTs;
    public bool $completed;
    #[Strlen(0, 9)]
    protected PasswordResetType $type;
    protected static string $salt = 'salt';
    protected static string $pepper = 'pepper';
    protected static bool $checkedIp = false;


    /** methods
     * @throws ResetPasswordTimeoutException
     */
    public static function checkPasswordResetAllowed(): void
    {
        // if we already did it on this request, then we're good
        if (self::$checkedIp) {
            return;
        }
        $ip = IP::getInstance();
        $event = self::class . ':login-attempt';
        if (!$ip->eventAllowed($event, static::PASSWORD_RESET_REQUESTS_ALLOWED, static::PASSWORD_RESET_REQUEST_TIMEOUT)) {
            throw new ResetPasswordTimeoutException(LoginErrorMessage::TooManyPasswordResetAttempts);
        }
        $ip->recordEvent($event);
        self::$checkedIp = true;
    }

    /**
     * starts the password reset process given a user
     * @param User $user
     * @param PasswordResetType $type
     * @return PasswordReset|null
     */
    public static function startFromUser(User $user, PasswordResetType $type = PasswordResetType::Reset): ?PasswordReset
    {
        $pr = new self();
        $pr->userId = $user->id;
        $pr->startTs = Time::getNow();
        $pr->completed = false;
        $pr->type = $type;
        $pr->save();
        $res = $pr->sendResetLink();
        if (!$res) {
            $pr->erase();
            return null;
        }
        return $pr;
    }

    /**
     * gets from the key
     * @param string $key
     * @return PasswordReset|false
     */
    public static function getFromKey(string $key): ?PasswordReset
    {
        // return first item in search by property or null if there aren't any
        $search = self::searchByProperty('key', $key);
        return empty($search) ? null : $search[0];
    }

    /**
     * are we within 24 hours of the reset request?
     * @return bool
     */
    public function isExpired(): bool
    {
        return (Time::getNow() - self::EXPIRES_AFTER) > $this->startTs;
    }

    /**
     * after completing the workflow, call this method so the reset can't be used again
     * @return void
     * @throws ValidationException
     */
    public function complete(): void
    {
        $this->update([
            'completed' => true
        ]);
    }

    public function getUser(): User
    {
        return User::readFromId($this->userId);
    }

    /**
     * send the email to the user's username, with the correct link
     * @return bool
     */
    public function sendResetLink(): bool
    {
        return Email::sendFromTemplate(
            'user/resetpassword',
            $this->getUser()->email,
            [
                'username' => $this->getUser()->username,
                'key' => $this->key
            ]
        );
    }

    /**
     * add the key password
     * @param array $changedProperties
     * @return array
     */
    protected function beforeSave(array $changedProperties): array
    {
        if (!isset($this->key)) {
            // we need to add the password hash value
            $this->key = md5(static::$salt . md5($this->userId . time()) . static::$pepper);
            return ['key'];
        }
        return [];
    }
}
