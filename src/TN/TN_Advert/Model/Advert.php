<?php

namespace TN\TN_Advert\Model;

use TN\TN_Core\Attribute\Constraints\Inclusion;
use TN\TN_Core\Attribute\Constraints\Strlen;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonJoin;
use TN\TN_Core\Model\PersistentModel\Search\SearchLimit;
use TN\TN_Core\Model\PersistentModel\Search\SearchLogical;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorterDirection;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Trait\Options\Audience;

/**
 * represents an advert displayed inside articles within time periods and to select users/visitors
 * @property-read array $locations
 * @property-read int $id
 *
 */
#[TableName('adverts')]
class Advert implements Persistence
{
    use MySQL;
    use PersistentModel;
    use Audience;

    const int FREQUENCY_DAILY = 1;
    const int FREQUENCY_ALTERNATE_DAYS = 2;
    const int FREQUENCY_WEEKLY = 3;
    const int FREQUENCY_TWO_WEEKS = 4;
    const int FREQUENCY_MONTHLY = 5;

    /**
     * @return array
     */
    public static function getAllFrequencies(): array
    {
        return [
            self::FREQUENCY_DAILY => 'Daily',
            self::FREQUENCY_ALTERNATE_DAYS => 'Once every two days',
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_TWO_WEEKS => 'Once every two weeks',
            self::FREQUENCY_MONTHLY => 'Monthly'
        ];
    }

    /* PROPERTIES */

    /** @var string title of the advert - plaintext */
    #[Strlen(3, 200)]
    public string $title = '';

    /** @var string advert itself - html */
    #[Strlen(3)]
    public string $advert = '';

    /** @var int weight for sorting adverts */
    public int $weight = 1;

    /** @var int current display frequency of an advert */
    public int $displayFrequency = self::FREQUENCY_DAILY;

    /** @var int show from this timestamp */
    public int $startTs;

    /** @var int show until this timestamp */
    public int $endTs;

    /** @var array a list of advert IDs that have already been gotten to display on this page */
    public static array $viewedAdvertIds = [];

    /**
     * who sees this message
     *
     * @see $audienceOptions
     * @var string
     */
    #[Inclusion('()getAudienceOptions|keys')]
    public string $audience = 'everyone';

    /** is this currently enabled? */
    public bool $enabled = false;

    /**
     * @return string
     */
    public function render(): string
    {
        return '<div class="tn-a-wrapper" data-advert-id="' . $this->id . '">' . $this->advert . '</div>';
    }

    /**
     * given a specific user, get all adverts currently available to them
     *
     * @param User $user
     * @param string $advertSpotKey must be a property or a underscore lowercase variant of one
     * @return Advert[]
     */
    public static function getShowableAdverts(User $user, string $advertSpotKey): array
    {
        $conditions = [];
        $conditions[] = new SearchComparison('`enabled`', '=', 1);
        $conditions[] = new SearchComparison('`startTs`', '<', Time::getNow());
        $conditions[] = new SearchLogical('or', [new SearchComparison('`endTs`', '>', Time::getNow()), new SearchComparison('`endTs`', '=', 0)]);
        $conditions[] = new SearchComparisonJoin(AdvertPlacement::class, static::class);
        $conditions[] = new SearchComparison('`' . AdvertPlacement::class . '`.`spotKey`', '=', $advertSpotKey);

        $adverts = [];
        foreach (self::search(new SearchArguments(conditions: $conditions)) as $advert) {
            if ($advert->isShowableForUserAt($user)) {
                $adverts[] = $advert;
            }
        }
        return $adverts;
    }

    public function setAdvertPlacements(array $advertSpotKeys): void
    {
        // delete all current advert placements
        AdvertPlacement::eraseFromAdvertId($this->id);

        // add new ones in - verify that each key in the $advertSpotKeys is valid
        $advertSpots = AdvertSpot::getInstances();
        $validSpotKeys = array_map(function ($advertSpot) {
            return $advertSpot->key;
        }, $advertSpots);

        $placements = [];

        foreach ($advertSpotKeys as $advertSpotKey) {
            // Verify if the spot key is valid
            if (in_array($advertSpotKey, $validSpotKeys)) {
                // Create a new advert placement
                $placement = AdvertPlacement::getInstance();
                $placement->advertId = $this->id;
                $placement->spotKey = $advertSpotKey;

                $placements[] = clone $placement;
            }
        }
        AdvertPlacement::batchSaveInsert($placements);
    }

    /**
     * given a specific user, get an advert currently available to them, randomly from those available
     * @param User $user
     * @param string $advertSpotKey must be a property or a underscore lowercase variant of mone
     * @return ?Advert
     * @throws ValidationException
     */
    public static function getAdvertToShow(User $user, string $advertSpotKey): ?Advert
    {
        $adverts = self::getShowableAdverts($user, $advertSpotKey);

        /* rule out any site messages that have been shown to the user within the last displayFrequency of the advert */
        if ($advertSpotKey === 'site_message') {
            $adverts = SiteMessageView::ruleOutRecentlySeenAdverts($adverts);
        }

        /* remove any adverts that have already been shown on this page view */
        $adverts = array_filter($adverts, function ($advert) {
            return !in_array($advert->id, self::$viewedAdvertIds);
        });

        $weightedAdverts = [];
        foreach ($adverts as $advert) {
            for ($i = 0; $i < $advert->weight; $i++) {
                $weightedAdverts[] = $advert;
            }
        }

        if (empty($weightedAdverts)) {
            return null;
        }

        $advert = $weightedAdverts[array_rand($weightedAdverts)];

        if ($advertSpotKey === 'site_message') {
            SiteMessageView::logView($advert);
        }

        /* add this advert's id into the array of adverts shown on this page */
        self::$viewedAdvertIds[] = $advert->id;

        return $advert;
    }

    /** @return string[] location options indexed by their property key */
    public static function getAdvertSpotOptions(): array
    {
        $locationInstances = AdvertSpot::getInstances();

        $advertSpotOptions = [];

        foreach ($locationInstances as $instance) {
            $advertSpotOptions[$instance->key] = [
                'name' => $instance->name,
                'description' => $instance->description,
                'sizeType' => $instance->sizeType
            ];
        }
        return $advertSpotOptions;
    }

    /** @return array of all adverts */
    public static function getAdverts(int $start = null, int $num = null, int $sortDirection = null, string $sortProperty = null, string $nameFilter = null): array
    {
        $conditions = [];
        $sorters = [];

        if ($nameFilter !== "" && $nameFilter !== null) {
            $conditions[] = new SearchComparison('`title`', 'LIKE', '%' . $nameFilter . '%');
        }

        if ($sortProperty !== null) {
            $sorters[] = new SearchSorter($sortProperty, $sortDirection === SORT_ASC ? SearchSorterDirection::ASC : SearchSorterDirection::DESC);
        }

        return static::search(new SearchArguments(conditions: $conditions, sorters: $sorters, limit: new SearchLimit($start, $num)));
    }

    /* METHODS */

    /**
     * magic getter
     *
     * @param string $prop
     * @return mixed
     */
    public function __get(string $prop): mixed
    {
        if ($prop === 'locations') {
            return $this->getLocations();
        }
        return (property_exists($this, $prop) && isset($this->$prop)) ? $this->$prop : null;
    }

    /**
     * get all the places where this advert can be shown
     * @return array
     */
    protected function getLocations(): array
    {
        $locations = [];
        foreach (self::getAdvertSpotOptions() as $key => $location) {
            if ($this->$key) {
                $locations[] = $location;
            }
        }
        return $locations;
    }

    /** @return bool */
    public function isActive(): bool
    {
        return $this->enabled && Time::getNow() > $this->startTs && (Time::getNow() < $this->endTs || $this->endTs === 0);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isShowableForUserAt(User $user): bool
    {
        return $this->isActive() && $this->userIsInAudience($user);
    }

}