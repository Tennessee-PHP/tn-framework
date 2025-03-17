<?php

namespace TN\TN_Core\Model\Provider\Discord\Guild;

use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Provider\Discord\User;

// todo: must replace the DiscordClient class because it conflicted with another library
abstract class Guild
{
    use \TN\TN_Core\Trait\Getter;
    use \TN\TN_Core\Trait\ExtendedSingletons;

    /** @var string the string identifier for which guild we are dealing with here */
    protected string $key;

    /** @var int the ID of the guild */
    protected int $guildId;

    /** @var array|int[] the list of roles IDs on discord, linked to TN roles as the key */
    protected array $rolesToDiscordRoles;

    /** @var array|int[] which TN plankeys to link to which discord role IDs */
    protected array $planLevelsToDiscordRoles;

    /** @var DiscordClient protected static instance of discord client */
    protected static DiscordClient $client;

    /** @return DiscordClient gets a discord client */
    protected function getClient(): DiscordClient
    {
        if (!isset(self::$client)) {
            self::$client = new DiscordClient(['token' => $_ENV['DISCORD_BOT_TOKEN']]);
        }
        return self::$client;
    }

    /**
     * returns array of integers of role ids that should belong to this user
     * @param User $user
     * @return array
     */
    protected function getUserRoleIdsToSet(User $user): array
    {
        $tnUser = $user->getTnUser();
        $roleIds = [];
        foreach ($tnUser->getRoles() as $role) {
            if (isset($this->rolesToDiscordRoles[$role->key])) {
                $roleIds[] = $this->rolesToDiscordRoles[$role->key];
            }
        }
        if (isset($this->planLevelsToDiscordRoles[$tnUser->getPlan()->key])) {
            $roleIds[] = $this->planLevelsToDiscordRoles[$tnUser->getPlan()->key];
        }
        return $roleIds;
    }

    /**
     * the current role ids that belong to this user
     * @param User $user
     * @return array
     * @throws ValidationException
     */
    protected function getCurrentUserRoleIds(User $user): array
    {
        try {
            $roleIds = $this->getClient()->guild->getGuildMember([
                'guild.id' => $this->guildId,
                'user.id' => $user->discordUserId
            ])->roles;
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '404 Not Found')) {
                throw new ValidationException('Please join our Discord server before linking your Discord account');
            }
        }
        if (!is_array($roleIds)) {
            $roleIds = [];
        }
        $controlledRoleIds = array_merge(array_values($this->rolesToDiscordRoles), array_values($this->planLevelsToDiscordRoles));
        return array_intersect($roleIds, $controlledRoleIds);
    }

    /**
     * remove a role id from the user
     * @param User $user
     * @param int $roleId
     * @return void
     */
    protected function removeUserRoleId(User $user, int $roleId): void
    {
        $this->getClient()->guild->removeGuildMemberRole([
            'guild.id' => $this->guildId,
            'user.id' => $user->discordUserId,
            'role.id' => $roleId
        ]);
    }

    /**
     * add a role id to the user
     * @param User $user
     * @param int $roleId
     * @return void
     */
    protected function addUserRoleId(User $user, int $roleId): void
    {
        $this->getClient()->guild->addGuildMemberRole([
            'guild.id' => $this->guildId,
            'user.id' => $user->discordUserId,
            'role.id' => $roleId
        ]);
    }

    /**
     * sets the users roles to be as they should be!
     * @param User $user
     * @return void
     * @throws \Exception
     */
    public function setUserRoles(User $user): void
    {
        $currentRoleIds = $this->getCurrentUserRoleIds($user);
        $correctRoleIds = $this->getUserRoleIdsToSet($user);
        foreach (array_diff($currentRoleIds, $correctRoleIds) as $roleId) {
            $this->removeUserRoleId($user, $roleId);
        }
        foreach (array_diff($correctRoleIds, $currentRoleIds) as $roleId) {
            $this->addUserRoleId($user, $roleId);
        }
    }
}