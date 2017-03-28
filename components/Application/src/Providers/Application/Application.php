<?php namespace Limoncello\Application\Providers\Application;

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

use ErrorException;
use Exception;
use Limoncello\Application\CoreSettings\CoreSettings;
use Limoncello\Application\ExceptionHandlers\DefaultHandler;
use Limoncello\Application\Settings\CacheSettingsProvider;
use Limoncello\Application\Settings\FileSettingsProvider;
use Limoncello\Application\Traits\SelectClassImplementsTrait;
use Limoncello\Contracts\Provider\ProvidesSettingsInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Core\Application\Sapi;
use Limoncello\Core\Contracts\Application\ExceptionHandlerInterface;
use Limoncello\Core\Contracts\Application\SapiInterface;
use Throwable;
use Zend\Diactoros\Response\SapiEmitter;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Limoncello\Container\Container;

/**
 * @package Limoncello\Application
 */
class Application extends \Limoncello\Core\Application\Application
{
    use SelectClassImplementsTrait;

    /**
     * @var string
     */
    private $settingsPath;

    /**
     * @var callable|string|null
     */
    private $settingCacheMethod;

    /**
     * Application constructor.
     *
     * @param string               $settingsPath
     * @param string|callable|null $settingCacheMethod
     * @param SapiInterface|null   $sapi
     */
    public function __construct(string $settingsPath, $settingCacheMethod = null, SapiInterface $sapi = null)
    {
        assert($settingCacheMethod === null || is_string($settingCacheMethod) === true);

        $this->settingsPath       = $settingsPath;
        $this->settingCacheMethod = $settingCacheMethod;

        $this->setSapi($sapi ?? new Sapi(new SapiEmitter()));
    }

    /**
     * @return FileSettingsProvider
     */
    public function createFileSettingsProvider(): FileSettingsProvider
    {
        $provider = (new FileSettingsProvider())->load($this->getSettingsPath());

        $appSettings = $provider->get(ApplicationSettings::class);

        $routesPath      = $appSettings[ApplicationSettings::KEY_ROUTES_PATH];
        $containersPath  = $appSettings[ApplicationSettings::KEY_CONTAINER_CONFIGURATORS_PATH];
        $providerClasses = $appSettings[ApplicationSettings::KEY_PROVIDER_CLASSES];

        // providers might have additional setting providers that also should be registered
        foreach ($this->selectProviders($providerClasses, ProvidesSettingsInterface::class) as $providerClass) {
            /** @var ProvidesSettingsInterface $providerClass */
            foreach ($providerClass::getSettings() as $setting) {
                $provider->register($setting);
            }
        }

        $coreSettings = new CoreSettings($routesPath, $containersPath, $providerClasses);

        $provider->register($coreSettings);

        return $provider;
    }

    /**
     * @return SettingsProviderInterface
     */
    protected function createSettingsProvider(): SettingsProviderInterface
    {
        if (is_callable($method = $this->getSettingCacheMethod()) === true) {
            $data     = call_user_func($method);
            $provider = (new CacheSettingsProvider())->setData($data);
        } else {
            $provider = $this->createFileSettingsProvider();
        }

        return $provider;
    }

    /**
     * @return LimoncelloContainerInterface
     */
    protected function createContainer(): LimoncelloContainerInterface
    {
        return new Container();
    }

    /**
     * @inheritdoc
     */
    protected function setUpExceptionHandler(SapiInterface $sapi, PsrContainerInterface $container)
    {
        error_reporting(E_ALL);

        $createHandler = function () use ($container) {
            $has     = $container->has(ExceptionHandlerInterface::class);
            $handler = $has === true ? $container->get(ExceptionHandlerInterface::class) : new DefaultHandler();

            return $handler;
        };

        $throwableHandler = function (Throwable $throwable) use ($sapi, $container, $createHandler) {
            /** @var ExceptionHandlerInterface $handler */
            $handler = $createHandler();
            $handler->handleThrowable($throwable, $sapi, $container);
        };

        $exceptionHandler = function (Exception $exception) use ($sapi, $container, $createHandler) {
            /** @var ExceptionHandlerInterface $handler */
            $handler = $createHandler();
            $handler->handleException($exception, $sapi, $container);
        };

        set_exception_handler(PHP_MAJOR_VERSION >= 7 ? $throwableHandler : $exceptionHandler);

        set_error_handler(function ($severity, $message, $fileName, $lineNumber) use ($exceptionHandler) {
            $errorException = new ErrorException($message, 0, $severity, $fileName, $lineNumber);
            $exceptionHandler($errorException);
            throw $errorException;
        });

        // handle fatal error
        register_shutdown_function(function () use ($container, $createHandler) {
            $error = error_get_last();
            if ($error !== null && ((int)$error['type'] & (E_ERROR | E_COMPILE_ERROR))) {
                /** @var ExceptionHandlerInterface $handler */
                $handler = $createHandler();
                $handler->handleFatal($error, $container);
            }
        });
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
