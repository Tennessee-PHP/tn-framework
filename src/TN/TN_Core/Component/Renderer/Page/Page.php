<?php

namespace TN\TN_Core\Component\Renderer\Page;

use TN\TN_CMS\Component\TagEditor\TagEditor;
use TN\TN_CMS\Model\PageEntry;
use TN\TN_Core\Attribute\Components\HTMLComponent\FullWidth;
use TN\TN_Core\Attribute\Components\HTMLComponent\RequiresChartJS;
use TN\TN_Core\Attribute\Components\HTMLComponent\RequiresTinyMCE;
use TN\TN_Core\Component\Error\Error;
use TN\TN_Core\Component\PageComponent;
use TN\TN_Core\Component\Provider\Meta\MetaPixel\MetaPixel;
use TN\TN_Core\Component\Renderer\Renderer;
use TN\TN_Core\Component\Renderer\TemplateRender;
use TN\TN_Core\Component\Title\Title as TitleComponent;
use TN\TN_Core\Component\User\LoginForm\LoginForm;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Attribute\Components\HTMLComponent\BareRender;
use TN\TN_Core\Attribute\Components\HTMLComponent\MetaPixelEvent;
use TN\TN_Core\Attribute\Components\HTMLComponent\RemoveNavigation;
use TN\TN_Core\Attribute\Components\HTMLComponent\RemoveFooter;
use TN\TN_Core\Attribute\Components\HTMLComponent\RequiresResource;
use TN\TN_Core\Component\Error\Maintenance\Maintenance;
use TN\TN_Core\Component\TemplateEngine;

/**
 * A whole page of the website to be outputted to the browser
 */
class Page extends Renderer
{
    use ReadOnlyProperties;
    use TemplateRender;

    /** @var string */
    public static string $contentType = 'text/html';
    public static bool $indexPagesToPageEntry = false;

    public string $template = 'TN_Core/Component/Renderer/Page/Page.tpl';

    public string $title = '';
    public ?string $subtitle = null;

    /**  @var ?string the meta description of the page for SEO
     * Google truncates this to max 160 characters. keep it as keyword-rich as possible */
    public ?string $description = null;

    /** @var ?string you can set an additional body class using this property */
    public ?string $bodyCls = null;

    /** @var ?string open graph type - use this to tell social media what type of content is on the page
     * @see https://ogp.me/#types
     */
    public ?string $openGraphType = null;

    /** @var ?string the image to use for open graph. If you leave this empty, Facebook "guesses" by parsing the page.
     * This must be a full URL. It is recommended to build this using $_ENV indices like BASE_URL or STATIC_BASE_URL.
     * @see https://ogp.me/#metadata
     */
    public ?string $openGraphImage = null;

    /** @var array associative array of javascript values to set on TN. */
    public array $jsVars = [];

    /** @var array any local JS should be built into the single minified file. This is for externally-hosted JS files. */
    public array $jsResources = [];

    /** @var array any local css should be built into the single minified file. This is for externally-hosted css files. */
    public array $cssResources = [];

    /** @var bool should navigation be removed from the page? */
    public bool $removeNavigation = false;

    /** @var bool should footer be removed from the page? */
    public bool $removeFooter = false;

    /** @var bool don't display any visual header HTML components */
    public bool $removeHeader = false;

    /** @var PageComponent */
    public PageComponent $component;

    public ?TitleComponent $titleComponent = null;

    public User $user;

    public LoginForm $loginForm;
    public bool $allowFullWidth;
    public ?PageEntry $pageEntry = null;
    public MetaPixel $metaPixel;

    /**
     * @param string $url adds an externally hosted javascript file to the page
     */
    public function addJsUrl(string $url): void
    {
        $this->addResource(new PageResource($url, PageResourceType::JS));
    }

    /**
     * adds an externally hosted css file to the page
     */
    public function addCssUrl($url): void
    {
        $this->addResource(new PageResource($url, PageResourceType::CSS));
    }

    public function addResource(PageResource $pageResource): void
    {
        if ($pageResource->type === PageResourceType::JS) {
            $this->jsResources[] = $pageResource;
        } else {
            $this->cssResources[] = $pageResource;
        }
    }

    public function addJsVar($key, $value = false): void
    {
        if (is_array($key)) {
            $this->jsVars = array_merge($this->jsVars, $key);
        } else {
            $this->jsVars[$key] = $value;
        }
    }

    /** @return array get the javascript variables */
    protected function getJsVars(): array
    {
        return $this->jsVars;
    }

    /** @return string[] open graph data to use */
    protected function getOpenGraphData(): array
    {
        $data = [
            'title' => $this->title,
            'type' => $this->openGraphType,
            'description' => $this->description,
            'locale' => 'en_US',
            'site_name' => $_ENV['SITE_NAME']
        ];
        if (!empty($this->openGraphImage)) {
            $data['image'] = $this->openGraphImage;
        }
        return $data;
    }

    public function addTinyMceResources(): void {}

    protected function addResources(): void
    {
        // let's just add everything in public/css/
        foreach (glob($_ENV['TN_WEB_ROOT'] . 'css/*.css') as $file) {
            $this->addResource(new PageResource(
                fileUrl: 'css/' . basename($file),
                type: PageResourceType::CSS,
                isRelative: true,
                liveReload: false, //$_ENV['ENV'] === 'development',
                cacheBuster: true
            ));
        }

        // add everything in public/js/
        foreach (glob($_ENV['TN_WEB_ROOT'] . 'js/*.js') as $file) {
            $this->addResource(new PageResource(
                fileUrl: 'js/' . basename($file),
                type: PageResourceType::JS,
                isRelative: true,
                liveReload: false,
                cacheBuster: true
            ));
        }

        // do we need to add tinymce or other client dependencies?
        $reflection = new \ReflectionClass($this->component);
        foreach ($reflection->getAttributes(RequiresResource::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attribute->newInstance()->addResource($this);
        }

        // call addTinyMceResources() if RequiresTinyMCE attribute is present
        if ($reflection->getAttributes(RequiresTinyMCE::class)) {
            $this->addTinyMceResources();
        }

        if ($reflection->getAttributes(FullWidth::class)) {
            $this->allowFullWidth = true;
        }

        if ($reflection->getAttributes(RemoveNavigation::class)) {
            $this->removeNavigation = true;
        }

        if ($reflection->getAttributes(RemoveFooter::class)) {
            $this->removeFooter = true;
        }

        foreach ($this->component->getPageJsVars() as $var => $value) {
            $this->addJsVar($var, $value);
        }

        // cloudflare turnstile?
        if ($_ENV['CLOUDFLARE_TURNSTILE_SITE_KEY']) {
            $this->addJsVar('CLOUDFLARE_TURNSTILE_SITE_KEY', $_ENV['CLOUDFLARE_TURNSTILE_SITE_KEY']);
            $this->addJsUrl('https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit');
        }
    }

    public function prepare(): void
    {
        // prepare the component first as it may mutate header/footer
        $this->addResources();
        $this->user = User::getActive();

        if (!$this->user->loggedIn) {
            $this->loginForm = new LoginForm();
            $this->loginForm->prepare();
        }

        $this->component->prepare();

        $this->user = User::getActive();
        $this->title = $this->component->getPageTitle();
        $this->description = $this->component->getPageDescription();
        $this->openGraphImage = $this->component->getPageOpenGraphImage();

        $this->metaPixel = new MetaPixel();
        $this->metaPixel->prepare();

        $reflection = new \ReflectionClass($this->component);
        if ($reflection->getAttributes(MetaPixelEvent::class)) {
            $metaPixelEvent = $reflection->getAttributes(MetaPixelEvent::class)[0]->newInstance();
            $arguments = [];
            if (method_exists($this->component, 'getMetaPixelArguments')) {
                $arguments = call_user_func([$this->component, 'getMetaPixelArguments']);
            }
            $this->metaPixel->event($metaPixelEvent->event, $arguments);
        }

        if (static::$indexPagesToPageEntry) {
            $this->indexPageToPageEntry();
        }

        $this->titleComponent = $this->component->getPageTitleComponent([
            'pageEntry' => $this->pageEntry
        ]);
        $this->titleComponent?->prepare();
    }

    protected function indexPageToPageEntry(): void
    {
        // first, let's check if the component needs to be indexed
        if (!$this->component->getPageIndex()) {
            return;
        }
        $contentPageEntry = $this->component->getContentPageEntry();
        if ($contentPageEntry) {
            $this->pageEntry = $contentPageEntry;
        } else {
            $this->pageEntry = PageEntry::addFromPage($this, $this->component->getPageIndexKey(), $this->component->getPageIndexPath());
        }

        $this->updatePageEntryTags();
    }

    protected function updatePageEntryTags(): void
    {
        if (!$this->pageEntry) {
            return;
        }

        $tags = $this->component->getPageEntryTags();
        if (empty($tags)) {
            return;
        }

        try {
            $tagEditor = new TagEditor($this->pageEntry);
            $tagEditor->updateTags($tags);
        } catch (ValidationException) {
            return;
        }
    }

    /**
     * @param string $message
     * @param int $httpResponseCode
     * @return Renderer
     */
    public static function error(string $message, int $httpResponseCode = 400): Renderer
    {
        return self::getInstance([
            'httpResponseCode' => $httpResponseCode,
            'title' => 'Error',
            'description' => 'An error occurred',
            'component' => new Error([
                'message' => nl2br($message)
            ])
        ]);
    }

    public static function maintenance(): Renderer
    {
        return self::getInstance([
            'httpResponseCode' => 503,
            'title' => 'Work in Progress!',
            'description' => $_ENV['SITE_MAINTENANCE_MESSAGE'],
            'component' => new Maintenance([
                'message' => $_ENV['SITE_MAINTENANCE_MESSAGE']
            ])
        ]);
    }

    public static function forbidden(): Renderer
    {
        return self::getInstance([
            'title' => 'Access Forbidden',
            'description' => 'An error occurred',
            'component' => new Error([
                'message' => 'Access forbidden'
            ])
        ]);
    }

    public static function loginRequired(): Renderer
    {
        return self::getInstance([
            'title' => 'Please Log In',
            'description' => 'An error occurred',
            'component' => new LoginForm()
        ]);
    }

    public static function uncontrolled(): Renderer
    {
        return self::getInstance([
            'title' => 'Access Forbidden',
            'description' => 'An error occurred',
            'component' => new Error([
                'message' => 'No access specified for route'
            ])
        ]);
    }

    public static function roadblock(): Renderer
    {
        return self::getInstance([
            'title' => 'Subscription Required',
            'description' => 'This content requires a subscription',
            'component' => new \TN\TN_Billing\Component\Roadblock\Roadblock\Roadblock()
        ]);
    }

    public function render(array $data = []): string
    {
        $reflection = new \ReflectionClass($this->component);
        if ($reflection->getAttributes(BareRender::class)) {
            return $this->component->render();
        }
        $engine = TemplateEngine::getInstance();
        $engine->assignData(array_merge($this->getTemplateData(), $data));
        return $engine->fetch($this->template);
    }
}
