<?php namespace Limoncello\Application\Packages\Application;

/**
 * Copyright 2015-2017 info@neomerx.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Limoncello\Application\Contracts\Settings\CacheSettingsProviderInterface;
use Limoncello\Application\CoreSettings\CoreSettings;
use Limoncello\Application\Settings\CacheSettingsProvider;
use Limoncello\Application\Settings\FileSettingsProvider;
use Limoncello\Application\Settings\InstanceSettingsProvider;
use Limoncello\Container\Container;
use Limoncello\Contracts\Application\ApplicationSettingsInterface as A;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Core\SapiInterface;
use Limoncello\Contracts\Provider\ProvidesSettingsInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Core\Application\Sapi;
use Limoncello\Core\Contracts\CoreSettingsInterface;
use Limoncello\Core\Reflection\ClassIsTrait;
use Zend\Diactoros\Response\SapiEmitter;

/**
 * @package Limoncello\Application
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Application extends \Limoncello\Core\Application\Application
{
    use ClassIsTrait;

    /**
     * @var string
     */
    private $settingsPath;

    /**
     * @var callable|string|null
     */
    private $settingCacheMethod;

    /**
     * @param string                     $settingsPath
     * @param string|array|callable|null $settingCacheMethod
     * @param SapiInterface|null         $sapi
     */
    public function __construct(string $settingsPath, $settingCacheMethod = null, SapiInterface $sapi = null)
    {
        // The reason we do not use `callable` for the input parameter is that at the moment
        // of calling the callable might not exist. Therefore when created it will pass
        // `is_callable` check and will be used for getting the cached data.
        assert(is_null($settingCacheMethod) || is_string($settingCacheMethod) || is_array($settingCacheMethod));

        $this->settingsPath       = $settingsPath;
        $this->settingCacheMethod = $settingCacheMethod;

        $this->setSapi($sapi ?? new Sapi(new SapiEmitter()));
    }

    /**
     * Get container from application. If `method` and `path` are specified the container will be configured
     * not only with global container configurators but with route's one as well.
     *
     * @param string|null $method
     * @param string|null $path
     *
     * @return LimoncelloContainerInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function createContainer(string $method = null, string $path = null): LimoncelloContainerInterface
    {
        $container = $this->createContainerInstance();

        $settingsProvider = $this->createSettingsProvider();
        $container->offsetSet(SettingsProviderInterface::class, $settingsProvider);
        $container->offsetSet(CacheSettingsProviderInterface::class, $settingsProvider);

        $coreSettings = $settingsProvider->get(CoreSettingsInterface::class);

        $routeConfigurators = [];
        if (empty($method) === false && empty($path) === false) {
            list(, , , , , $routeConfigurators) = $this->initRouter($coreSettings)->match($method, $path);
        }

        // configure container
        $globalConfigurators = CoreSettings::getGlobalConfiguratorsFromData($coreSettings);
        $this->configureContainer($container, $globalConfigurators, $routeConfigurators);

        return $container;
    }

    /**
     * @return SettingsProviderInterface
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function createSettingsProvider(): SettingsProviderInterface
    {
        $provider = new CacheSettingsProvider();
        if (is_callable($method = $this->getSettingCacheMethod()) === true) {
            $data = call_user_func($method);
            $provider->unserialize($data);
        } else {
            $provider->setInstanceSettings($this->createFileSettingsProvider());
        }

        return $provider;
    }

    /**
     * @return InstanceSettingsProvider
     */
    protected function createFileSettingsProvider(): InstanceSettingsProvider
    {
        // Load all settings from path specified
        $provider = (new FileSettingsProvider())->load($this->getSettingsPath());

        // Application settings have a list of providers which might have additional settings to load
        $appSettings     = $provider->get(A::class);
        $providerClasses = $appSettings[A::KEY_PROVIDER_CLASSES];
        foreach ($this->selectClassImplements($providerClasses, ProvidesSettingsInterface::class) as $providerClass) {
            /** @var ProvidesSettingsInterface $providerClass */
            foreach ($providerClass::getSettings() as $setting) {
                $provider->register($setting);
            }
        }

        // App settings (paths, lists) --> core settings (container configurators, routes, middleware and etc).
        $routesPath     = $appSettings[A::KEY_ROUTES_PATH];
        $containersPath = $appSettings[A::KEY_CONTAINER_CONFIGURATORS_PATH];
        $coreSettings   = new CoreSettings($routesPath, $containersPath, $providerClasses);

        $provider->register($coreSettings);

        return $provider;
    }

    /**
     * @return LimoncelloContainerInterface
     */
    protected function createContainerInstance(): LimoncelloContainerInterface
    {
        return new Container();
    }

    /**
     * @return string
     */
    protected function getSettingsPath(): string
    {
        return $this->settingsPath;
    }

    /**
     * @return callable|string|null
     */
    protected function getSettingCacheMethod()
    {
        return $this->settingCacheMethod;
    }
}
