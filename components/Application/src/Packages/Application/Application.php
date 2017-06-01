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

use Closure;
use ErrorException;
use Limoncello\Application\Contracts\Settings\CacheSettingsProviderInterface;
use Limoncello\Application\CoreSettings\CoreSettings;
use Limoncello\Application\ExceptionHandlers\DefaultHandler;
use Limoncello\Application\Settings\CacheSettingsProvider;
use Limoncello\Application\Settings\FileSettingsProvider;
use Limoncello\Application\Settings\InstanceSettingsProvider;
use Limoncello\Contracts\Core\SapiInterface;
use Limoncello\Contracts\Exceptions\ExceptionHandlerInterface;
use Limoncello\Contracts\Provider\ProvidesSettingsInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Core\Application\Sapi;
use Limoncello\Core\Contracts\CoreSettingsInterface;
use Limoncello\Core\Reflection\ClassIsTrait;
use Throwable;
use Zend\Diactoros\Response\SapiEmitter;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Limoncello\Container\Container;

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
        $appSettings     = $provider->get(ApplicationSettings::class);
        $providerClasses = $appSettings[ApplicationSettings::KEY_PROVIDER_CLASSES];
        foreach ($this->selectClassImplements($providerClasses, ProvidesSettingsInterface::class) as $providerClass) {
            /** @var ProvidesSettingsInterface $providerClass */
            foreach ($providerClass::getSettings() as $setting) {
                $provider->register($setting);
            }
        }

        // App settings (paths, lists) --> core settings (container configurators, routes, middleware and etc).
        $routesPath     = $appSettings[ApplicationSettings::KEY_ROUTES_PATH];
        $containersPath = $appSettings[ApplicationSettings::KEY_CONTAINER_CONFIGURATORS_PATH];
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
     * @inheritdoc
     */
    protected function setUpExceptionHandler(SapiInterface $sapi, PsrContainerInterface $container)
    {
        error_reporting(E_ALL);

        set_exception_handler($this->createThrowableHandler($sapi, $container));
        set_error_handler($this->createErrorHandler($sapi, $container));
        register_shutdown_function($this->createFatalErrorHandler($container));
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

    /**
     * @param PsrContainerInterface $container
     *
     * @return ExceptionHandlerInterface
     */
    protected function createExceptionHandler(PsrContainerInterface $container): ExceptionHandlerInterface
    {
        $has     = $container->has(ExceptionHandlerInterface::class);
        $handler = $has === true ? $container->get(ExceptionHandlerInterface::class) : new DefaultHandler();

        return $handler;
    }

    /**
     * @param SapiInterface         $sapi
     * @param PsrContainerInterface $container
     *
     * @return Closure
     */
    protected function createThrowableHandler(SapiInterface $sapi, PsrContainerInterface $container): Closure
    {
        return function (Throwable $throwable) use ($sapi, $container) {
            $handler = $this->createExceptionHandler($container);
            $handler->handleThrowable($throwable, $sapi, $container);
        };
    }

    /**
     * @param SapiInterface         $sapi
     * @param PsrContainerInterface $container
     *
     * @return Closure
     */
    protected function createErrorHandler(SapiInterface $sapi, PsrContainerInterface $container): Closure
    {
        return function ($severity, $message, $fileName, $lineNumber) use ($sapi, $container) {
            $errorException = new ErrorException($message, 0, $severity, $fileName, $lineNumber);
            $handler = $this->createThrowableHandler($sapi, $container);
            $handler($errorException);
            throw $errorException;
        };
    }

    /**
     * @param PsrContainerInterface $container
     *
     * @return Closure
     */
    protected function createFatalErrorHandler(PsrContainerInterface $container): Closure
    {
        return function () use ($container) {
            $error = $this->getLastError();
            if ($error !== null && ((int)$error['type'] & (E_ERROR | E_COMPILE_ERROR))) {
                $handler = $this->createExceptionHandler($container);
                $handler->handleFatal($error, $container);
            }
        };
    }

    /**
     * It is needed for mocking while testing.
     *
     * @return array|null
     */
    protected function getLastError()
    {
        return error_get_last();
    }
}
