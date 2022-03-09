<?php

/**
 * This file is part of the Phlexus CMS.
 *
 * (c) Phlexus CMS <cms@phlexus.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phlexus\Modules\Shop;

use Phalcon\Di\DiInterface;
use Phalcon\Loader;
use Phalcon\Mvc\View\Engine\Volt;
use Phlexus\Module as PhlexusModule;
use Phlexus\Helpers;
use Phlexus\Modules\BaseUser\Acl\DefaultAcl;
use Phlexus\Modules\BaseUser\Events\Listeners\DispatcherListener;
use Phlexus\Modules\BaseUser\Events\Listeners\AuthorizationListener;

/**
 * User Module
 */
class Module extends PhlexusModule
{
    /**
     * Get Module Name
     * 
     * @return string
     */
    public static function getModuleName(): string
    {
        $namespaceParts = explode('\\', __NAMESPACE__);

        return end($namespaceParts);
    }

    /**
     * Get Handlers Namespace
     * 
     * @return string
     */
    public static function getHandlersNamespace(): string
    {
        return __NAMESPACE__;
    }

    /**
     * Registers an autoloader related to the module.
     *
     * @param DiInterface $di
     *
     * @return void
     */
    public function registerAutoloaders(DiInterface $di = null): void
    {
        (new Loader())
            ->registerNamespaces([
                self::getHandlersNamespace() . '\\Models' => __DIR__ . '/Models/',
                self::getHandlersNamespace() . '\\Controllers' => __DIR__ . '/Controllers/',
                self::getHandlersNamespace() . '\\Providers' => __DIR__ . '/Providers/',
            ])
            ->register();
    }

    /**
     * Register Services
     * 
     * @param DiInterface|null $di
     * 
     * @return void
     */
    public function registerServices(DiInterface $di = null): void
    {
        $view = $di->getShared('view');
        $theme = Helpers::phlexusConfig('theme');

        $themePath = $theme->themes_dir . $theme->theme_user;
        $cacheDir = $theme->themes_dir_cache;

        $view->registerEngines([
            '.volt' => function ($view) use ($cacheDir, $di) {
                $volt = new Volt($view, $di);
                $volt->setOptions([
                    'path' => $cacheDir,
                ]);

                $compiler = $volt->getCompiler();
                $compiler->addFunction('assetsPath', '\Phlexus\Helpers::phlexusAssetsPath');

                return $volt;
            }
        ]);

        $view->setMainView($themePath . '/layouts/public');
        $view->setViewsDir($themePath . '/');

        // Default Acl
        $acl = new DefaultAcl();
        $di->set('acl', $acl);

        $di->getShared('eventsManager')->attach('dispatch', new DispatcherListener());
        $di->getShared('eventsManager')->attach('dispatch:beforeDispatchLoop', new AuthorizationListener());
    }
}
