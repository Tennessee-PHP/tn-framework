<?php

namespace TN\TN_Core\Model\User;

use PDO;
use Random\RandomException;
use TN\TN_Billing\Model\Customer\Braintree\Customer;
use TN\TN_Billing\Model\Refund\Refund;
use TN\TN_Billing\Model\Subscription\Content\Content;
use TN\TN_Billing\Model\Subscription\GiftSubscription;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\Subscription\SubscriptionOrganizer;
use TN\TN_Billing\Model\Transaction\Transaction;
use TN\TN_Billing\Model\VoucherCode;
use TN\TN_Core\Attribute\Encrypt;
use TN\TN_Core\Attribute\Constraints\EmailAddress;
use TN\TN_Core\Attribute\Constraints\OnlyContains;
use TN\TN_Core\Attribute\Constraints\Strlen;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\MySQL\Timestamp;
use TN\TN_Core\Attribute\Optional;
use TN\TN_Core\Error\Access\AccessForbiddenException;
use TN\TN_Core\Error\Login\LoginErrorMessage;
use TN\TN_Core\Error\Login\LoginException;
use TN\TN_Core\Error\TNException;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\Email\Email;
use TN\TN_Core\Model\HashMethod\HashMethod;
use TN\TN_Core\Model\IP\IP;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonArgument;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonJoin;
use TN\TN_Core\Model\PersistentModel\Search\SearchLimit;
use TN\TN_Core\Model\PersistentModel\Search\SearchLogical;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Role\OwnedRole;
use TN\TN_Core\Model\Role\Role;
use TN\TN_Core\Model\Role\RoleGroup;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Storage\Redis;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Attribute\Cache;
use TN\TN_Core\Error\CodeException;

/**
 * a user of the website
 *
 * Handles authentication of users with hashed encrypted passwords
 *
 * Locked: checks how many IP addresses are associated with an account, prevents account sharing.
 * Inactive: abusive customers, users requesting multiple refunds, etc. If user info is dumped via security breach of
 * other site, allows for quick disabling of account at users request
 */
#[TableName('users')]
class User implements Persistence
{
    use MySQL;
    use PersistentModel;

    /**
     * constants
     */

    /** @var int how long should a login last */
    const int LOGIN_EXPIRES = Time::ONE_MONTH * 6;

    /** @var int how many different IP addresses a user can use prior to being locked */
    const int IP_LIMIT = 8;

    /** @var int how many login attempts are allowed before lock-out */
    const int LOGIN_ATTEMPTS_ALLOWED = 5;

    /** @var int how long to wait when locked out */
    const int LOGIN_ATTEMPT_TIMEOUT = 600;

    /** @var int how long users are limited to self::IP_LIMIT */
    const int IP_TIMEFRAME = 3600;

    protected static string $defaultHashMethodKey = 'tn';
    protected static ?User $activeUser = null;

    #[Impersistent] public bool $loggedIn = false;
    #[Timestamp] public int $createdTs;
    public bool $locked = false;
    public bool $inactive = false;
    #[Strlen(1, 50)] #[OnlyContains('A-Za-z0-9 \._-', 'letters, numbers, periods, underscores and dashes')] public string $username;
    #[EmailAddress] public string $email;
    public string $hash;
    public string $hashMethodKey;
    /** Current-session token from user_tokens; not persisted to users table */
    #[Impersistent] public string $token = '';
    #[Impersistent] protected ?array $roles = null;

    /** @var string the user's password - in practice, only ever set in PHP during a registration attempt.
     * After this, the password is only stored in hash'ed form. */
    #[Impersistent] #[Strlen(6, 50)] #[Optional] public string $password;
    #[Impersistent] public string $passwordRepeat;
    public string $last;
    public string $first;

    /** TOTP secret for 2FA (encrypted at rest). Null if user has not enrolled. */
    #[Encrypt] public ?string $totpSecret = null;

    /** methods */

    /**
     * loads up an object from mysql, given its id
     * @param string $token
     * @return User|null
     */
    public static function readFromToken(string $token = ''): ?User
    {
        if (empty($token)) {
            return null;
        }
        $userToken = UserToken::findValidByToken($token);
        if ($userToken === null) {
            return null;
        }
        $user = static::readFromId($userToken->userId);
        if (!$user instanceof User) {
            return null;
        }
        $user->token = $userToken->token;
        return $user;
    }

    /**
     * magic getter
     *
     * @param string $prop
     * @return mixed
     */
    public function __get(string $prop): mixed
    {
        return match ($prop) {
            'id' => $this->id ?? 0,
            'name' => trim($this->first . ' ' . $this->last),
            default => property_exists($this, $prop) ? ($this->$prop ?? null) : null
        };
    }

    /**
     * @return void add custom validations for a user
     * @throws ValidationException
     */
    protected function customValidate(): void
    {
        $errors = [];

        if (isset($this->password)) {
            // are the passwords the same - if the id is not set?
            if ($this->password !== $this->passwordRepeat) {
                $errors[] = 'The two copies of the password did not match';
            }
        }

        // if the id is not set: are the username and email already existing?
        $usernameMatches = $this->reduceDownToOtherUsersOnly(self::searchByProperty('username', $this->username));
        if (count($usernameMatches) > 0) {
            $errors[] = 'An account with this username already exists';
        }
        $emailMatches = $this->reduceDownToOtherUsersOnly(self::searchByProperty('email', $this->email));
        if (count($emailMatches) > 0) {
            $errors[] = 'An account with this email address already exists';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    protected function reduceDownToOtherUsersOnly(array $otherUsers): array
    {
        if (!isset($this->id)) {
            return $otherUsers;
        }
        $returnOtherUsers = [];
        foreach ($otherUsers as $otherUser) {
            if ($otherUser->id !== $this->id) {
                $returnOtherUsers[] = $otherUser;
            }
        }
        return $returnOtherUsers;
    }

    /** @return string generates a random password */
    public static function generateRandomPassword(): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!$*&';
        $pass = [];
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    /**
     * takes an email, spews out a valid username
     * @param string $email
     * @return string
     */
    public static function emailToUniqueUsername(string $email): string
    {
        $parts = explode('@', $email);
        $res = preg_replace("/[^A-Za-z0-9._-]/", "", $parts[0]);
        $res = substr($res, 0, 40);
        while (strlen($res) < 6) {
            $res .= 'x';
        }
        $count = 0;
        $original = $res;
        while (self::getFromLogin($res) instanceof User) {
            $count += 1;
            $res = $original . $count;
        }
        return $res;
    }

    /**
     * the login is either a username or email
     * @param string $login
     * @return User|null
     */
    public static function getFromLogin(string $login): ?User
    {
        return static::searchOne(new SearchArguments(
            conditions: new SearchLogical(
                'OR',
                [
                    new SearchComparison('`username`', '=', $login),
                    new SearchComparison('`email`', '=', $login)
                ]
            )
        ));
    }

    /**
     * if there's a logged in user, return them. If not, return blank user with loggedIn set to false
     * @param bool $recalculate
     * @return User
     */
    public static function getActive(bool $recalculate = false): User
    {
        if (!isset(self::$activeUser) || $recalculate) {
            self::setActiveUser();
        }
        return self::$activeUser;
    }

    private static function setNoActiveUser(): void
    {
        self::$activeUser = self::getInstance();
        self::$activeUser->loggedIn = false;
    }

    /**
     * Clear active user state for testing
     */
    public static function clearActiveUser(): void
    {
        self::$activeUser = null;
    }

    public static function getUsersWithRole(string $roleKey): array
    {
        $role = Role::getInstanceByKey($roleKey);
        if (!$role) {
            return [];
        }

        $conditions = [
            new SearchComparisonJoin(joinFromClass: OwnedRole::class, joinToClass: User::class)
        ];
        if ($role instanceof RoleGroup) {
            $roleConditions = [];
            foreach ($role->getChildren() as $role) {
                $roleConditions[] = new SearchComparison(new SearchComparisonArgument(property: 'roleKey', class: OwnedRole::class), '=', $role->key);
            }
            $conditions[] = new SearchLogical('OR', $roleConditions);
        } else {
            $conditions[] = new SearchComparison(new SearchComparisonArgument(property: 'roleKey', class: OwnedRole::class), '=', $roleKey);
        }

        $users = [];
        $userIds = [];
        foreach (
            self::search(new SearchArguments(
                conditions: $conditions
            )) as $user
        ) {
            if (!in_array($user->id, $userIds)) {
                $users[] = $user;
                $userIds[] = $user->id;
            }
        }
        return $users;
    }

    public static function setActiveUserFromToken(string $token): void
    {
        if (empty($token)) {
            return;
        }
        $userToken = UserToken::findValidByToken($token);
        if ($userToken === null) {
            return;
        }
        $user = static::readFromId($userToken->userId);
        if (!$user instanceof User) {
            return;
        }
        $user->token = $userToken->token;
        self::setUserAsActive($user);
    }

    /** set the active user */
    private static function setActiveUser(): void
    {
        try {
            $request = HTTPRequest::get();
        } catch (CodeException $e) {
            static::setNoActiveUser();
            return;
        }

        if ($request->getSession('TN_LoggedIn_User_Id', null) !== null) {
            $sessionUserId = $request->getSession('TN_LoggedIn_User_Id');
            $user = static::readFromId($sessionUserId, true);
            if ($user instanceof User) {
                self::setUserAsActive($user);
                return;
            }
        }

        $token = $request->getAuthToken();
        $tokenSource = $request->getAuthTokenSource();

        if ($token === null || $token === '') {
            static::setNoActiveUser();
            return;
        }

        $userToken = UserToken::findValidByToken($token);
        if ($userToken === null) {
            static::setNoActiveUser();
            return;
        }

        $user = static::readFromId($userToken->userId, true);
        if (!$user instanceof User) {
            static::setNoActiveUser();
            return;
        }
        $user->token = $userToken->token;
        self::setUserAsActive($user);

        if ($tokenSource === 'body') {
            self::persistSessionAndCookieForUser($user);
        }
    }

    /** @param User $user we found a user - set it as active! */
    private static function setUserAsActive(User $user): void
    {
        $request = HTTPRequest::get();
        $user->logIPLogin();

        $tnLoginAsUserId = $request->getSession('TN_LoginAs_User_Id');
        $baseUserId = $user->id;
        $hasAdminRole = $user->hasRole('super-user') || $user->hasRole('user-admin');

        // Resolve impersonation: session first, then TN_LoginAs_token cookie (for requests that don't send session cookie, e.g. GET /)
        $otherUser = null;
        if (!empty($tnLoginAsUserId)) {
            $otherUser = static::readFromId((int)$tnLoginAsUserId);
        } else {
            $loginAsToken = $request->getCookie('TN_LoginAs_token');
            if ($loginAsToken !== null && $loginAsToken !== '') {
                $loginAsUserToken = UserToken::findValidByToken($loginAsToken);
                if ($loginAsUserToken !== null) {
                    $otherUser = static::readFromId($loginAsUserToken->userId);
                    if ($otherUser instanceof User) {
                        $otherUser->token = $loginAsUserToken->token;
                    }
                }
            }
        }
        if ($otherUser instanceof User && $hasAdminRole) {
            $user = $otherUser;
        }

        self::$activeUser = $user;
        self::$activeUser->loggedIn = true;
    }

    /**
     * Set session and TN_token cookie for the user (single place for persist logic).
     * @param User $user
     * @return void
     */
    private static function persistSessionAndCookieForUser(User $user): void
    {
        $request = HTTPRequest::get();
        $request->setSession('TN_LoggedIn_User_Id', $user->id);
        if (!defined('UNIT_TESTING') || !constant('UNIT_TESTING')) {
            $request->setCookie('TN_token', $user->token, [
                'expires' => Time::getNow() + self::LOGIN_EXPIRES,
                'secure' => $_ENV['ENV'] !== 'development',
                'domain' => $_ENV['COOKIE_DOMAIN'],
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    /**
     * @return HashMethod
     */
    protected function getHashMethod(): HashMethod
    {
        if (empty($this->hashMethodKey)) {
            $this->hashMethodKey = static::$defaultHashMethodKey;
        }
        return HashMethod::getInstanceByKey($this->hashMethodKey);
    }

    /**
     * @return array
     */
    protected function setPasswordHash(): array
    {
        $hashMethod = $this->getHashMethod();
        $hashData = $hashMethod->getHashData($this->password);
        foreach ($hashMethod->getHashData($this->password) as $key => $value) {
            $this->$key = $value;
        }
        return array_keys($hashData);
    }

    /**
     * generate the hash password
     * @param array $changedProperties
     * @return array
     * @throws RandomException
     */
    protected function beforeSave(array $changedProperties): array
    {
        if (!isset($this->id) || in_array('password', $changedProperties)) {
            $isPasswordChange = isset($this->id) && in_array('password', $changedProperties);
            // we need to add the password hash value
            $changedProperties = $this->setPasswordHash();
            if ($isPasswordChange) {
                UserToken::revokeAllForUser($this->id);
            }
            if (!isset($this->createdTs)) {
                $this->createdTs = Time::getNow();
                $changedProperties[] = 'createdTs';
            }
            return $changedProperties;
        }
        return [];
    }

    /** they must have just registered somehow so send them a welcome email */
    protected function afterSaveInsert(): void
    {
        if ($_ENV['ENV'] === 'production') {
            Email::sendFromTemplate(
                'user/registered',
                $this->email,
                [
                    'username' => $this->username,
                    'email' => $this->email
                ]
            );
        }
    }

    /**
     * checks whether the password entered correctly matches the hash
     * @param string $password
     * @return bool
     * @throws RandomException
     * @throws ValidationException
     */
    public function verifyPassword(string $password): bool
    {
        $hashMethod = $this->getHashMethod();

        // if $this has salt, set it to $salt; otherwise, set it to an empty string
        if (property_exists($this, 'salt')) {
            $salt = $this->salt;
        } else {
            $salt = '';
        }

        if (!$hashMethod->verify($password, $this->hash, $salt)) {
            return false;
        }

        $this->logIPLogin();
        $this->updateLocked();

        return true;
    }

    /**
     * @param string $login
     * @param string $password
     * @return bool
     * @throws LoginException
     * @throws RandomException
     * @throws ValidationException
     */
    public static function attemptLogin(string $login, string $password): bool
    {
        $ip = IP::getInstance();
        $event = self::class . ':attempt-login';
        if (!$ip->eventAllowed($event, static::LOGIN_ATTEMPTS_ALLOWED, static::LOGIN_ATTEMPT_TIMEOUT)) {
            throw new LoginException(LoginErrorMessage::Timeout);
        }

        // process a login attempt
        $user = static::getFromLogin($login);
        if ($user && $user->verifyPassword($password)) {
            if ($user->inactive) {
                throw new LoginException(LoginErrorMessage::Inactive);
            } else if ($user->locked) {
                throw new LoginException(LoginErrorMessage::Locked);
            } else {
                $user->login($password);
                return true;
            }
        } else {
            $ip->recordEvent($event);
            throw new LoginException(LoginErrorMessage::InvalidCredentials, static::LOGIN_ATTEMPTS_ALLOWED - $ip->eventCount($event, static::LOGIN_ATTEMPT_TIMEOUT));
        }
    }

    /**
     * attempts to log in this user - returns true or false
     * @param string $password
     * @return bool
     * @throws RandomException
     * @throws ValidationException
     */
    public function login(string $password): bool
    {
        if (!$this->verifyPassword($password)) {
            return false;
        }
        $this->doLogin();
        return true;
    }

    /**
     * Log in using a one-time code (login identifier and 6-digit code).
     * @param string $login Username or email
     * @param string $code 6-digit login code
     * @throws TNException When user not found, code invalid, expired, or already used
     * @throws RandomException
     * @throws ValidationException
     */
    public static function attemptLoginFromCode(string $login, string $code): void
    {
        $user = static::getFromLogin($login);
        if (!$user instanceof User) {
            throw new TNException('Invalid or expired code');
        }
        $loginCode = LoginCode::findValidForUserAndCode($user->id, $code);
        if ($loginCode === null) {
            throw new TNException('Invalid or expired code');
        }
        $loginCode->update(['used' => true]);
        $user->doLogin();
    }

    /**
     * @return void user has passed all login checks
     * @throws RandomException
     * @throws ValidationException
     */
    public function doLogin(): void
    {
        $userToken = UserToken::createForUser($this);
        $this->token = $userToken->token;
        self::persistSessionAndCookieForUser($this);

        // associate this IP login with this user
        $this->logIPLogin();
        // Use setUserAsActive($this) so the active user keeps this instance (with token). If we
        // called setActiveUser() it would re-load from session and get a fresh User with no token.
        self::setUserAsActive($this);
    }

    /**
     * Check if this user can login as another user
     * @param User $otherUser
     * @return bool
     */
    public function canLoginAsUser(User $otherUser): bool
    {
        // Framework default: false if other user is super-user, otherwise return if this user is super-user
        if ($otherUser->hasRole('super-user')) {
            return false;
        }
        return $this->hasRole('super-user');
    }

    /**
     * login as a different user
     * @param int $otherUserId
     * @return void
     * @throws ValidationException
     * @throws AccessForbiddenException
     */
    public function loginAs(int $otherUserId): void
    {
        $request = HTTPRequest::get();
        if (!$this->hasRole('user-admin')) {
            throw new AccessForbiddenException('You do not have permission to login as another user');
        }

        $otherUser = static::readFromId($otherUserId);
        if (!$otherUser instanceof User) {
            throw new ValidationException('User not found');
        }

        if (!$this->canLoginAsUser($otherUser)) {
            throw new ValidationException('You cannot login as this user');
        }

        $tnLoginAsUserId = $request->getSession('TN_LoginAs_User_Id', null);
        if (empty($tnLoginAsUserId)) {
            $request->setSession('TN_LoginAs_User_Id', $otherUserId);
            $loginAsUserToken = UserToken::createForUser($otherUser);
            if (!defined('UNIT_TESTING') || !constant('UNIT_TESTING')) {
                $request->setCookie('TN_LoginAs_token', $loginAsUserToken->token, [
                    'expires' => Time::getNow() + self::LOGIN_EXPIRES,
                    'secure' => $_ENV['ENV'] !== 'development',
                    'domain' => $_ENV['COOKIE_DOMAIN'],
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
        }
        $this->setActiveUser();
    }

    /**
     * return to parent login
     * @return bool
     * @see self::loginAs()
     */
    public function returnToBaseLogin(): bool
    {
        $request = HTTPRequest::get();
        $request->setSession('TN_LoginAs_User_Id', null);
        $request->setCookie('TN_LoginAs_token', '', [
            'expires' => Time::getNow() + self::LOGIN_EXPIRES,
            'secure' => true,
            'domain' => $_ENV['COOKIE_DOMAIN'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $this->setActiveUser();
        return true;
    }

    /** returns if user is logged in as a different user */
    public function isLoggedInAsOther(): bool
    {
        $request = HTTPRequest::get();
        if ($request->getSession('TN_LoginAs_User_Id', null) !== null) {
            return true;
        }
        $loginAsToken = $request->getCookie('TN_LoginAs_token');
        return $loginAsToken !== null && $loginAsToken !== '';
    }

    /** @return string the redis key for the hash to store ip addresses' last access timestamps */
    private function getIPHashKey(): string
    {
        return 'UserIPLogins-id:' . $this->id;
    }

    /** @return string get the visitor's IP address */
    private function getRemoteAddress(): string
    {
        $request = HTTPRequest::get();
        return $request->getPost('ip', null) ?? ($request->getServer('HTTP_X_FORWARDED_FOR', null) ?? 'xxx');
    }

    /**
     * log that this IP just logged in as this user
     */
    protected function logIPLogin(): void
    {
        $client = Redis::getInstance();
        $client->hset($this->getIPHashKey(), $this->getRemoteAddress(), Time::getNow());
    }

    /**
     * update this user to be locked or not, depending on their IP usage
     * @throws ValidationException
     */
    protected function updateLocked(): void
    {
        $client = Redis::getInstance();
        $ipLogins = $client->hgetall($this->getIPHashKey());
        if (!is_array($ipLogins)) {
            $ipLogins = [];
        }
        $threshold = Time::getNow() - self::IP_TIMEFRAME;
        $ipCount = 0;
        foreach ($ipLogins as $ip => $ts) {
            if ($ts > $threshold) {
                $ipCount += 1;
            }
        }
        $locked = ($ipCount > self::IP_LIMIT);
        if ($locked !== $this->locked) {
            $this->update([
                'locked' => $locked
            ]);
            if ($locked) {
                Email::sendFromTemplate(
                    //                    'Locked Account for ' . $_ENV['SITE_NAME'],
                    'user/locked',
                    $this->email,
                    [
                        'username' => $this->username,
                        'ipLimit' => self::IP_LIMIT,
                        'hours' => ceil(self::IP_TIMEFRAME) / 3600
                    ]
                );
            }
        }
    }

    /** @return bool logs the user out */
    public function logout(): bool
    {
        if ($this->isLoggedInAsOther()) {
            $this->returnToBaseLogin();
            return true;
        }
        $request = HTTPRequest::get();
        $request->setSession('TN_LoggedIn_User_Id', null);
        if (!defined('UNIT_TESTING') || !constant('UNIT_TESTING')) {
            $request->setCookie('TN_token', '', [
                'expires' => Time::getNow() + self::LOGIN_EXPIRES,
                'secure' => true,
                'domain' => $_ENV['COOKIE_DOMAIN'],
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        static::setActiveUser();
        return true;
    }

    /**
     * Revoke all tokens for the given user (e.g. admin revoking for another user).
     */
    public static function revokeAllTokensForUser(User $user): void
    {
        UserToken::revokeAllForUser($user->id);
    }

    /**
     * Revoke all tokens for the currently active user and log out the current request.
     *
     * @return bool true if revoke and logout happened, false if no active user
     */
    public static function revokeAllTokensForSelf(): bool
    {
        $user = static::getActive();
        if (!$user->loggedIn) {
            return false;
        }
        UserToken::revokeAllForUser($user->id);
        $user->logout();
        return true;
    }

    /** events on a users subscriptions changing */
    public function subscriptionsChanged(): void
    {
        $organizer = new SubscriptionOrganizer($this);
        $organizer->organize();
    }

    /**
     * @return void remove any personally identifiable information from a user
     * @throws ValidationException
     */
    public function dePersonalizeUser(): void
    {
        $this->update([
            'username' => 'REMOVED-' . $this->id,
            'email' => 'removed@mail.com'
        ]);

        UserInactiveChange::createAndSave($this, $this, false, 'Users personal data removed and account de-activated at their own request');
    }

    /**
     * merge another user into this one
     * @param User $secondaryUser
     * @param User $byUser
     * @return bool
     * @throws ValidationException
     * @throws TNException
     */
    public function mergeWithUser(User $secondaryUser, User $byUser): bool
    {
        if (!$byUser->hasRole('user-admin') || $secondaryUser->inactive) {
            throw new ValidationException('User does not have permission or the secondary user is inactive.');
        }

        // make the other user inactive with a reason
        $comment = 'Merging with user ' . $this->username . ' (#' . $this->id . ')';
        UserInactiveChange::createAndSave($secondaryUser, $byUser, false, $comment);

        // make sure this user has all the roles of the secondary user
        foreach ($secondaryUser->getRoles() as $role) {
            $this->addRole($role->key);
        }

        // user inactive changes
        foreach (UserInactiveChange::getUserChanges($secondaryUser) as $change) {
            $change->update([
                'userId' => $this->id
            ]);
        }

        // move all the subscriptions over from $secondaryUser
        foreach (Subscription::getUserSubscriptions($secondaryUser) as $subscription) {
            $subscription->update([
                'userId' => $this->id
            ]);
        }

        $this->subscriptionsChanged();

        // claimedByUserId on gift subscriptions
        foreach (GiftSubscription::searchByProperty('claimedByUserId', $secondaryUser->id) as $giftSubscription) {
            $giftSubscription->update([
                'claimedByUserId' => $this->userId
            ]);
        }

        // migrate all transactions over to this new user
        foreach (Transaction::getAllFromUser($secondaryUser) as $transaction) {
            $transaction->update([
                'userId' => $this->id
            ]);
        }

        // migrate all refunds over to this new user
        foreach (Refund::searchByProperty('userId', $secondaryUser->id) as $refund) {
            $refund->update([
                'userId' => $this->id
            ]);
        }

        // migrate the braintree customer over to this new user IF we don't already have one here
        $secondaryCustomer = Customer::getFromUser($secondaryUser);
        if ($secondaryCustomer instanceof Customer) {
            $thisCustomer = Customer::getFromUser($this);
            if (!($thisCustomer instanceof Customer)) {
                $secondaryCustomer->update([
                    'userId' => $this->id
                ]);
            } else {
                if ($secondaryCustomer->hasVaultedToken() && !$thisCustomer->hasVaultedToken()) {
                    // better to keep the secondary one!
                    $thisCustomer->erase();
                } else {
                    // better or no difference to keep this one
                    $secondaryCustomer->erase();
                }
            }
        }

        return true;
    }

    /**
     * any necessary actions after a user's subscription to a plan begins
     * @param Transaction $transaction
     * @param mixed $product
     */
    public function transactionSuccessful(Transaction $transaction, mixed $product): void {}

    /** @param Plan $plan any necessary actions after a user's subscription to a plan ends */
    public function unsubscribedFrom(Plan $plan): void {}

    /** @param VoucherCode $voucherCode any necessary actions after a user has used a voucher code */
    public function usedVoucherCode(VoucherCode $voucherCode): void {}

    /** @return Plan|null gets the active user's plan */
    public function getPlan(): ?Plan
    {
        return Plan::getActiveUserPlan($this);
    }

    /** @return Subscription|null gets the users current active subscription, if one exists */
    public function getActiveSubscription(): ?Subscription
    {
        if (!isset($this->id)) {
            return null;
        }
        return Subscription::getUserActiveSubscription($this);
    }

    /** @return bool does the user have an active braintree subscription? */
    public function hasActiveBraintreeSubscription(): bool
    {
        $subscription = $this->getActiveSubscription();
        return $subscription instanceof Subscription && $subscription->gatewayKey === 'braintree';
    }

    /** @return string */
    public function getUniversalIdentifier(): string
    {
        $request = HTTPRequest::get();
        return (isset($this->id) && $this->id > 0) ? (string)$this->id : (string)($request->getServer('REMOTE_ADDR', 'unknown'));
    }

    /** @return bool */
    public function isPaidSubscriber(): bool
    {
        return $this->getPlan()->paid ?? false;
    }

    /**
     * is a user able to view this content?
     * @param string $contentKey
     * @return bool
     */
    public function canViewContent(string $contentKey): bool
    {
        $content = Content::getInstanceByKey($contentKey);
        $plan = $this->getPlan();
        if ($plan === false || $content === false) {
            return false;
        }
        return $plan->level >= $content->level;
    }

    /**
     * get a list of all the roles. Should return array of role objects
     * @return array
     */
    public function getRoles(): array
    {
        if (!isset($this->id)) {
            return [];
        }

        // Return cached roles if available
        if ($this->roles !== null) {
            return $this->roles;
        }

        $roleGroups = [];
        $usedKeys = [];
        $ownedRoles = OwnedRole::searchByProperty('userId', $this->id, true);
        $roles = [];
        foreach ($ownedRoles as $ownedRole) {
            $role = Role::getInstanceByKey($ownedRole->roleKey);
            if ($role) {
                $roles[] = $role;
            } else {
                // Key exists in DB but no Role subclass is registered (e.g. app-specific roles).
                // Include a stub so getRoleKeys() and hasRole() reflect all owned keys.
                $roles[] = (object)['key' => $ownedRole->roleKey, 'roleGroup' => null];
            }
        }

        foreach ($roles as $role) {
            while ($role->roleGroup) {
                if (!array_search($role->roleGroup, $usedKeys)) {
                    if (Role::getInstanceByKey($role->roleGroup) !== null) {
                        $roleGroups[] = Role::getInstanceByKey($role->roleGroup);
                        $usedKeys[] = $role->roleGroup;
                    }
                }
                $role = Role::getInstanceByKey($role->roleGroup);
            }
        }

        // Cache the result
        $this->roles = array_merge($roles, array_unique($roleGroups));
        return $this->roles;
    }

    /**
     * Clear the roles cache, forcing next getRoles() call to fetch fresh data
     */
    protected function clearRolesCache(): void
    {
        $this->roles = null;
    }

    /**
     * add a role with the given key
     * @param string $key
     */
    public function addRole(string $key): void
    {
        // if $key is for a role-group throw an exception
        if (Role::getInstanceByKey($key) === false || $this->hasRole($key) || !isset($this->id)) {
            return;
        }

        $ownedRole = OwnedRole::getInstance();
        $ownedRole->update([
            'userId' => $this->id,
            'roleKey' => $key
        ]);

        $this->clearRolesCache(); // Clear cache when roles change
        $this->rolesChanged();
        Role::getInstanceByKey($key)->roleOwnerAdded($this);
    }

    /**
     * remove a role with the given key
     * @param string $key
     */
    public function removeRole(string $key): void
    {
        // if $key is for a role-group throw an exception
        if (Role::getInstanceByKey($key) === false || !$this->hasRole($key) || !isset($this->id)) {
            return;
        }

        $ownedRoles = OwnedRole::searchByProperties([
            'userId' => $this->id,
            'roleKey' => $key
        ]);

        foreach ($ownedRoles as $ownedRole) {
            $ownedRole->erase();
        }

        $this->clearRolesCache(); // Clear cache when roles change
        $this->rolesChanged();
        Role::getInstanceByKey($key)->roleOwnerRemoved($this);
    }

    public function rolesChanged(): void
    {
        // may wish to do something here on an override
    }

    /**
     * get an array of owned keys of roles
     * @return string[]
     */
    public function getRoleKeys(): array
    {
        $roleKeys = [];
        foreach ($this->getRoles() as $role) {
            $roleKeys[] = $role->key;
        }
        return $roleKeys;
    }

    /**
     * has a specific role?
     * @param string $key
     * @return bool
     */
    public function hasRole(string $key): bool
    {
        // get the role instance by the key
        $role = Role::getInstanceByKey($key);
        if (!$role || !isset($this->id)) {
            return false;
        }

        // First check if the role exists in getRoles()
        foreach ($this->getRoles() as $userRole) {
            if ($userRole->key === $key) {
                return true;
            }
        }

        // If it's a role group, check that separately
        if (is_array($role->children)) {
            return $this->hasRoleGroup($key);
        }

        return false;
    }

    /**
     * has a role group?
     * @param string $key
     * @return bool
     */
    public function hasRoleGroup(string $key): bool
    {
        foreach ($this->getRoles() as $role) {
            if ($role instanceof RoleGroup && $role->key === $key) {
                return true;
            }
        }
        return false;
    }

    /**
     * returns instances of roles owned by this user in this role group
     * @param string $roleGroupKey
     * @return Role[]
     */
    public function getRolesInRoleGroup(string $roleGroupKey): array
    {
        $roles = [];
        foreach ($this->getRoles() as $role) {
            if ($role->isInRoleGroup($roleGroupKey)) {
                $roles[] = $role;
            }
        }
        return $roles;
    }

    /**
     * Search for users by username for autocomplete
     * @param string $term
     * @return array
     */
    public static function autocomplete(string $term): array
    {
        // Fetch 6 results (5 to show + 1 to check if there are more)
        $users = static::search(new SearchArguments(
            conditions: new SearchComparison('`username`', 'LIKE', '%' . $term . '%'),
            limit: new SearchLimit(0, 6)
        ));

        $results = [];
        foreach ($users as $user) {
            $results[] = $user->username;
        }
        return $results;
    }
}
