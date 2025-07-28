<?php

namespace TN\TN_Core\Model\Provider\Discord;

use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Provider\ThirdPartyUser;
use TN\TN_Core\Model\User\User as TNUser;
use Wohali\OAuth2\Client\Provider\Discord as DiscordProvider;

#[TableName('discord_users')]
class User extends ThirdPartyUser
{
    /** @var int the discord user ID */
    public int $discordUserId;

    /** @var string discord username */
    public string $username;

    /** @var string discord oauth token */
    public string $oAuthToken;

    /** @var int when the oauth token expires */
    public int $tokenExpires;

    /** @var string discord refresh token */
    public string $refreshToken;

    public static function getOAuthProvider(): DiscordProvider
    {
        return new DiscordProvider([
            'clientId' => $_ENV['DISCORD_APPLICATION_ID'],
            'clientSecret' => $_ENV['DISCORD_OAUTH_SECRET'],
            'redirectUri' => $_ENV['BASE_URL'] . 'me/profile/connected-accounts/discord'
        ]);
    }

    /** @return void redirect the user to the oauth2 login */
    public static function redirectToLogin(): void
    {
        $provider = self::getOAuthProvider();
        $authUrl = $provider->getAuthorizationUrl([
            'state' => 'OPTIONAL_CUSTOM_CONFIGURED_STATE',
            'scope' => ['identify', 'guilds']
        ]);
        header('Location: ' . $authUrl);
    }

    /**
     * @param string $code
     * @param TNUser $tnUser
     * @return User
     * @throws ValidationException|\League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public static function createFromOAuthCode(string $code, TNUser $tnUser): User
    {
        $provider = self::getOAuthProvider();
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $code
        ]);

        $discordUser = $provider->getResourceOwner($token);
        $user = self::getInstance();
        $user->update([
            'oAuthToken' => $token->getToken(),
            'tokenExpires' => $token->getExpires(),
            'refreshToken' => $token->getRefreshToken(),
            'tnUserId' => $tnUser->id,
            'discordUserId' => $discordUser->getId(),
            'username' => $discordUser->getUsername()
        ]);
        $user->onCreate();
        return $user;
    }

    /** @return void actions immediately after a user is created */
    protected function onCreate(): void {}
}
