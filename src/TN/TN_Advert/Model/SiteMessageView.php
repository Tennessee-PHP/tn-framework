<?php

namespace TN\TN_Advert\Model;

use PDO;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\IP\IP;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Trait\Options\Audience;

#[TableName('site_message_view')]
/**
 * Represents a view log of site messages shown to users.
 *
 * This class handles the persistence and retrieval of site message view records,
 * which track when and which users have viewed specific site messages.
 */
class SiteMessageView implements Persistence
{
    use MySQL;
    use PersistentModel;
    use Audience;

    /* PROPERTIES */

    /** @var int id of associated advert */
    public int $advertId;

    /** @var string username of user who viewed this associated advert */
    public string $userIdentifier = '';

    /** @var int timestamp of when advert was viewed */
    public int $ts;

    /**
     * Filters out adverts that have been seen recently by the active user.
     *
     * @param Advert[] $adverts An array of adverts to be filtered.
     * @return Advert[] An array of adverts that have not been seen recently by the user.
     */
    public static function ruleOutRecentlySeenAdverts(array $adverts): array
    {
        $filteredAdverts = [];
        foreach ($adverts as $advert) {
            if (!self::advertIsRecentlySeen($advert)) {
                $filteredAdverts[] = $advert;
            }
        }
        return $filteredAdverts;
    }

    /**
     * Checks if an advert has been recently seen by the user.
     *
     * @param Advert $advert The advert to check.
     * @return bool True if the advert has been recently seen, false otherwise.
     */
    public static function advertIsRecentlySeen(Advert $advert): bool
    {
        return self::searchOne(new SearchArguments([
            new SearchComparison('`advertId`', '=', $advert->id),
            new SearchComparison('`userIdentifier`', '=', self::getUserIdentifier()),
            new SearchComparison('`ts`', '>=', Time::getNow() - self::convertFrequencyToSeconds($advert->displayFrequency))
        ])) instanceof SiteMessageView;
    }

    /**
     * user ID or their IP address if not logged in
     * @return string
     */
    public static function getUserIdentifier(): string
    {
        return (string) (User::getActive()->id ?? IP::getAddress());
    }

    /**
     * Logs the view of an advert for the current user.
     *
     * @param Advert $advert The advert being viewed.
     * @return void
     * @throws ValidationException
     */
    public static function logView(Advert $advert): void
    {
        $siteMessageView = SiteMessageView::getInstance();
        $siteMessageView->update([
            'advertId' => $advert->id,
            'userIdentifier' => self::getUserIdentifier(),
            'ts' => Time::getNow()
        ]);
    }

    /**
     * Converts display frequency to seconds.
     *
     * @param int $frequency The display frequency constant.
     * @return int|float The frequency in seconds.
     */
    private static function convertFrequencyToSeconds(int $frequency): float|int
    {
        return match ($frequency) {
            Advert::FREQUENCY_DAILY => Time::ONE_DAY,
            Advert::FREQUENCY_ALTERNATE_DAYS => Time::ONE_DAY * 2,
            Advert::FREQUENCY_WEEKLY => Time::ONE_WEEK,
            Advert::FREQUENCY_TWO_WEEKS => Time::ONE_WEEK * 2,
            Advert::FREQUENCY_MONTHLY => Time::ONE_MONTH,
            default => 0,
        };
    }
}