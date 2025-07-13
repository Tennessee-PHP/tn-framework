<?php

namespace TN\TN_Core\Component\User\UserProfile;

use TN\TN_Billing\Attribute\Components\HTMLComponent\RequiresBraintree;
use TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Attribute\Components\HTMLComponent\RequiresTinyMCE;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Error\ResourceNotFoundException;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\User\User;

#[Page('User Profile', 'User profile', false)]
#[Route('TN_Core:User:userProfile')]
#[RequiresTinyMCE]
#[RequiresBraintree]
class UserProfile extends HTMLComponent
{
    public string $username;
    public ?User $user;
    public array $tabs = [];
    public ?string $tab = null;
    public UserProfileTab $tabComponent;
    public bool $canLoginAsUser = false;

    public function getPageTitle(): string
    {
        return 'Profile for ' . $this->user->username;
    }

    public function prepare(): void
    {
        if ($this->username === 'me') {
            $this->user = User::getActive();
        } else {
            $this->user = User::searchOne(new SearchArguments(conditions: new SearchComparison('`id`', '=', $this->username)), true);
            if (!$this->user) {
                $this->user = User::searchOne(new SearchArguments(conditions: new SearchComparison('`username`', '=', $this->username)), true);
            }

            if (!User::getActive()->hasRole('super-user') && $this->user->id !== User::getActive()->id) {
                throw new ResourceNotFoundException('Cannot view this user');
            }
        }

        if (!$this->user) {
            throw new ResourceNotFoundException('Cannot find this user');
        }

        $this->username = $this->user->username;
        $this->canLoginAsUser = User::getActive()->hasRole('super-user');

        $selectedTabClass = null;
        $tabSortOrders = [];
        foreach (Stack::getChildClasses(UserProfileTab::class) as $class) {
            $class = Stack::resolveClassName($class);
            if (!$class::enabled($this->user)) {
                continue;
            }

            $this->tabs[] = [
                'key' => $class::$tabKey,
                'readable' => $class::$tabReadable,
                'selected' => $this->tab === $class::$tabKey,
                'class' => $class,
                'sortOrder' => $class::$sortOrder
            ];
            $tabSortOrders[] = $class::$sortOrder;

            if ($this->tab === $class::$tabKey) {
                $selectedTabClass = $class;
            }
        }
        array_multisort($tabSortOrders, SORT_ASC, $this->tabs);

        if (!$selectedTabClass) {
            $this->tabs[0]['selected'] = true;
            $this->tab = $this->tabs[0]['key'];
            $selectedTabClass = $this->tabs[0]['class'];
        }

        $this->tabComponent = new $selectedTabClass(['user' =>  $this->user, 'username' => $this->username]);
        $this->tabComponent->prepare();
    }
}
