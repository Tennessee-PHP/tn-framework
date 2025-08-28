<?php

namespace TN\TN_Core\Component;

/**
 * HTMLComponent is the base class for all HTML-rendered components in the TN Framework.
 * It provides core functionality for template rendering, page attributes, and component identification.
 *
 * Components extending this class must:
 * - Have a corresponding .tpl template file
 * - Implement the prepare() method
 * - Can optionally have .ts and .scss files
 *
 * Features:
 * - Automatic CSS class generation based on component hierarchy
 * - Template data preparation
 * - Page metadata handling (title, description, breadcrumbs)
 * - Support for reloadable components
 * - Path and query parameter injection
 *
 * @example
 * ```php
 * #[Page('User Profile', 'View user profile details')]
 * #[Reloadable]
 * #[Route('User:Profile:view')]
 * class UserProfile extends HTMLComponent {
 *     #[FromPath] public string $userId;
 *
 *     #[FromQuery] public string $tab = 'info';
 *
 *     public User $user;
 *     
 *     public function prepare(): void {
 *         $this->user = User::readFromId($this->userId);
 *         $this->title = "Profile: {$this->user->name}";
 *     }
 * }
 * ```
 *
 * Corresponding template (UserProfile.tpl):
 * ```smarty
 * <div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
 *     <h1>{$user->name}</h1>
 *     <div class="profile-content">
 *         {* Profile content here *}
 *     </div>
 * </div>
 * ```
 *
 * @see TemplateComponent
 * @see PageComponent
 */

use TN\TN_CMS\Model\PageEntry;
use TN\TN_Core\Attribute\Components\FromPath;
use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Attribute\Components\IndexByValue;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\Title\BreadcrumbEntry;
use TN\TN_Core\Component\Title\Title;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Error\CodeException;
use TN\TN_Core\Model\Theme\Theme;

/**
 * Base abstract class for all components in the TN Framework.
 * 
 * Components are the fundamental building blocks of the application, representing
 * reusable pieces of functionality that can be rendered and composed together.
 * This class provides core component functionality including:
 * 
 * - Property injection from HTTP requests
 * - Attribute-based parameter binding
 * - Lifecycle management (construction, preparation, rendering)
 * - Base component interface definition
 *
 * @abstract
 * @package TN\TN_Core\Component
 */

/**
 * an HTML component (rendered via a template, and with a typescript/scss file optional)
 */
abstract class HTMLComponent extends TemplateComponent implements PageComponent
{
    /** @var string */
    public string $classAttribute;

    /** @var string */
    public string $idAttribute;

    public string $requestKey;

    protected ?string $title = null;
    protected ?string $subtitle = null;
    protected ?string $description = null;
    
    /** @var bool Whether this component is being rendered as part of a full page or standalone */
    public bool $isFullPageRender;

    /**
     * @return string
     */
    public static function generateClassAttribute(): string
    {
        return str_replace("\\", "-", strtolower(static::class));
    }

    /**
     * @param array $options
     */
    public function __construct(array $options = [], array $pathArguments = [])
    {
        parent::__construct($options, $pathArguments);

        // Get the render mode from the request (already determined by framework routing)
        $request = \TN\TN_Core\Model\Request\HTTPRequest::get();
        $this->isFullPageRender = $request->isFullPageRender;

        // get a reflection class of the static class
        $reflection = new \ReflectionClass(static::class);
        $classAttributes = ['tnc-component'];

        while ($reflection && $reflection->getName() !== self::class) {
            // get the css class of the current reflection class
            $classAttributes[] = ($reflection->getName())::generateClassAttribute();
            $reflection = $reflection->getParentClass();
        }

        $this->classAttribute = implode(' ', $classAttributes);
        $this->idAttribute = 'tnc_' . $this->num;
    }

    public function getTemplateData(): array
    {
        $data = [
            'id' => $this->getHtmlId(),
            'theme' => Theme::getTheme(),
            'title' => $this->getPageTitle(),
            'breadcrumbEntries' => $this->getBreadcrumbEntries()
        ];
        $pageAttribute = $this->getFirstAttributeInstance(Page::class);
        $routeAttribute = $this->getFirstAttributeInstance(Route::class);
        if ($pageAttribute) {
            $data['pageTitle'] = $pageAttribute->title;
            $data['pageRoute'] = $routeAttribute?->route ?? '';
            $data['pageDescription'] = $pageAttribute->description;
        }
        if ($this->getFirstAttributeInstance(Reloadable::class)) {
            $data['reloadRoute'] = $routeAttribute?->route ?? '';
        }
        return array_merge($data, get_object_vars($this));
    }

    protected function getFirstAttributeInstance(string $className): mixed
    {
        // get a reflection class of this
        $reflection = new \ReflectionClass($this);

        // try to read attributes of type $className
        try {
            foreach ($reflection->getAttributes() as $attribute) {
                if ($attribute->getName() === $className) {
                    return $attribute->newInstance();
                }
            }
        } catch (\Error $e) {
            throw new CodeException($e->getMessage() . ' on ' . static::class);
        }

        return null;
    }

    public function getPageIndex(): bool
    {
        return $this->getFirstAttributeInstance(Page::class)?->index ?? false;
    }

    public function getPageIndexKey(): string
    {
        $keyParts = [];
        $properties = [];
        foreach ($this->getPropertiesFrom(IndexByValue::class) as $property => $value) {
            $keyParts[] = $property . ':' . $value;
            $properties[] = $property;
        }
        array_multisort($properties, SORT_ASC, $keyParts);

        if (!empty($keyParts)) {
            $keyString = '-' . implode('-', $keyParts);
        } else {
            $keyString = '';
        }

        return $this->getFirstAttributeInstance(Route::class)?->route . $keyString;
    }

    public function getPageIndexPath(): string
    {
        $route = $this->getFirstAttributeInstance(Route::class)?->route ?? '';
        if (empty($route)) {
            throw new CodeException('Route is not set for ' . static::class);
        }
        $parts = explode(':', $route);
        if (count($parts) !== 3) {
            throw new CodeException('Route ' . $route . ' is not a valid route on ' . static::class);
        }
        $queryData = $this->getPropertiesFrom(FromQuery::class);
        if (!empty($queryData)) {
            $query = '?' . http_build_query($queryData);
        } else {
            $query = '';
        }

        $path = Controller::path($parts[0], $parts[1], $parts[2], $this->getPropertiesFrom(FromPath::class)) . $query;

        // now trim $_ENV['BASE_URL'] off the start
        return substr($path, strlen($_ENV['BASE_URL']));
    }

    protected function getPropertiesFrom(string $attributeClass): array
    {
        // get all the properties on this class that have an attribute of $attributeClass
        $reflection = new \ReflectionClass($this);
        $properties = [];
        foreach ($reflection->getProperties() as $property) {
            foreach ($property->getAttributes($attributeClass) as $attribute) {
                $propertyName = $property->getName();
                $properties[$propertyName] = $this->$propertyName ?? null;
            }
        }
        return $properties;
    }

    public function getPageTitle(): string
    {
        return $this->title ?? ($this->getFirstAttributeInstance(Page::class)?->title ?? '');
    }

    public function getPageSubtitle(): ?string
    {
        return $this->subtitle;
    }

    /** @return BreadcrumbEntry[] */
    public function getBreadcrumbEntries(): array
    {
        // get all the breadcrumb attributes of this reflection class
        $reflection = new \ReflectionClass($this);
        $breadcrumbEntries = [];
        foreach ($reflection->getAttributes(Breadcrumb::class) as $breadcrumbAttribute) {
            $breadcrumbEntries[] = $breadcrumbAttribute->newInstance()->getBreadcrumbEntry();
        }
        return $breadcrumbEntries;
    }

    public function getPageTitleComponent(array $options): ?Title
    {
        return new Title(array_merge([
            'title' => $this->getPageTitle(),
            'breadcrumbEntries' => $this->getBreadcrumbEntries(),
            'subtitle' => $this->getPageSubtitle()
        ], $options));
    }

    public function getPageRoute(): string
    {
        return $this->getFirstAttributeInstance(Route::class)?->route ?? '';
    }

    public function getPageDescription(): string
    {
        return $this->description ?? ($this->getFirstAttributeInstance(Page::class)?->description ?? '');
    }

    public function getPageOpenGraphImage(): ?string
    {
        return null;
    }

    public function getPageJsVars(): array
    {
        return [];
    }

    public function getReloadRoute(): string
    {
        return $this->getFirstAttributeInstance(Route::class)?->route ?? '';
    }

    public function getContentPageEntry(): ?PageEntry
    {
        return null;
    }

    public function getPageEntryTags(): array
    {
        return [];
    }


}
