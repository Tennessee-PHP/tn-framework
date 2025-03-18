<?php

namespace TN\TN_Core\Component;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Component\Renderer\Page\Page;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\Package\Package;

class ComponentMap
{
    protected array $map;

    protected string $typeScriptPath = 'src/ts/componentMap.ts';
    protected string $sassPath = 'src/scss/_components.scss';

    /**
     * @return void
     */
    public static function write(): void
    {
        $map = self::getInstance();
        $map->writeTypeScriptFile();
        $map->writeSassFile();
    }

    protected static function getInstance(): ComponentMap
    {
        return new (Stack::resolveClassName(self::class))();
    }

    /**
     * @return void
     */
    public function __construct()
    {
        $this->map = [];
        $this->generateMap();
    }

    /**
     * @return void
     */
    protected function generateMap(): void
    {
        // let's get all the components!
        $classNames = Stack::getChildClasses(HTMLComponent::class);
        foreach ($classNames as $className) {
            $this->map[$className::generateClassAttribute()] = $className;
        }
    }

    protected function writeTypeScriptFile(): void
    {
        $includes = [];
        $maps = [];

        foreach ($this->map as $cssClass => $className) {
            // Get the package name from the fully qualified class name
            $parts = explode("\\", $className);
            $packageName = $parts[0];
            
            // Find the package instance
            $package = Package::get($packageName);
            if (!$package) {
                continue; // Skip if package not found
            }

            $qualified = str_replace("\\", "_", $className);
            $path = str_replace("\\", "/", $className);

            // Check if TypeScript file exists in package directory
            if (!file_exists($package->getDir() . "{$path}.ts")) {
                continue;
            }

            // Convert path to use TypeScript path alias
            $aliasPath = $this->getTypeScriptAliasPath($path);
            if ($aliasPath === null) {
                continue; // Skip if no matching alias found
            }

            $includes[] = "import {$qualified} from \"{$aliasPath}\";";
            $maps[] = "    '{$cssClass}': {$qualified}";
        }

        // Handle Page import
        $pageClassName = Page::class;
        $pageParts = explode("\\", $pageClassName);
        $pagePackage = Package::get($pageParts[0]);
        
        $pagePath = str_replace("\\", "/", Stack::resolveClassName(Page::class));
        if ($pagePackage && file_exists($pagePackage->getDir() . "src/{$pagePath}.ts")) {
            $pageAliasPath = $this->getTypeScriptAliasPath($pagePath);
            if ($pageAliasPath !== null) {
                $includes[] = "import Page from \"{$pageAliasPath}\";";
            }
        } else {
            // Fallback to direct class path
            $pagePath = str_replace("\\", "/", Page::class);
            $pageAliasPath = $this->getTypeScriptAliasPath($pagePath);
            if ($pageAliasPath !== null) {
                $includes[] = "import Page from \"{$pageAliasPath}\";";
            }
        }

        $str = implode("\n", $includes) . "\n\n";
        $str .= <<<TYPESCRIPT

type ComponentMap = {
    [key: string]: Function
}

const componentMap: object = {

TYPESCRIPT;
        $str .= implode(",\n", $maps) . "\n};\n\n";
        $str .= <<<TYPESCRIPT

const pageReference: typeof Page = Page;

export default componentMap as ComponentMap;
export {componentMap, pageReference};
TYPESCRIPT;
        file_put_contents($_ENV['TN_ROOT'] . $this->typeScriptPath, $str);
    }

    /**
     * Convert a file path to its corresponding TypeScript path alias
     * @param string $path The file path to convert
     * @return string|null The TypeScript path alias or null if no matching alias found
     */
    private function getTypeScriptAliasPath(string $path): ?string
    {
        // Common package prefixes and their TypeScript aliases
        $aliasMap = [
            'TN' => '@tn',
            'FBG' => '@fbg',
        ];

        foreach ($aliasMap as $prefix => $alias) {
            if (str_starts_with($path, $prefix . '/')) {
                return $alias . substr($path, strlen($prefix));
            }
        }

        // If path starts with 'ts/', use the @ts alias
        if (str_starts_with($path, 'ts/')) {
            return '@ts' . substr($path, 2);
        }

        return null;
    }

    protected function writeSassFile(): void
    {
        // let's resolve the page class, and just plain include its file
        $pageClassName = Stack::resolveClassName(Page::class);
        $pageParts = explode("\\", $pageClassName);
        $last = array_pop($pageParts);
        $pageParts[] = '_' . $last;
        $pagePackage = Package::get($pageParts[0]);
        array_shift($pageParts);
        $path = implode('/', $pageParts);
        
        if ($pagePackage && file_exists($pagePackage->getDir() . "{$path}.scss")) {
            $str = "@import \"@{$pageParts[0]}/{$path}\";\n\n";
        } else {
            $str = "// Page SCSS not found\n\n";
        }

        foreach ($this->map as $className) {
            $parts = explode("\\", $className);
            $packageName = array_shift($parts);
            // put a lodash prefix on the last element of the array
            $last = array_pop($parts);
            $parts[] = '_' . $last;
            $path = implode('/', $parts);
            
            $package = Package::get($packageName);
            if (!$package) {
                continue;
            }

            if (file_exists($package->getDir() . "{$path}.scss")) {
                $str .= "@import \"@{$packageName}/{$path}\";\n";
            }
        }
        
        file_put_contents($_ENV['TN_ROOT'] . $this->sassPath, $str);
    }


}