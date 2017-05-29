<?php namespace Limoncello\Application\Commands;

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

use Limoncello\Application\Exceptions\ConfigurationException;
use Limoncello\Contracts\Application\ApplicationSettingsInterface;
use Limoncello\Contracts\Commands\CommandInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\FileSystem\FileSystemInterface;
use Limoncello\Contracts\Serializable\ArraySerializableInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Application
 */
class ApplicationCommand implements CommandInterface
{
    /** Argument name */
    const ARG_ACTION = 'action';

    /** Command action */
    const ACTION_CLEAR_CACHE = 'clear-cache';

    /** Command action */
    const ACTION_CREATE_CACHE = 'cache';

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'l:app';
    }

    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return 'Creates and cleans application cache.';
    }

    /**
     * @inheritdoc
     */
    public static function getHelp(): string
    {
        return 'This command creates and cleans caches for routes, settings and etc.';
    }

    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        $cache = static::ACTION_CREATE_CACHE;
        $clear = static::ACTION_CLEAR_CACHE;

        return [
            [
                static::ARGUMENT_NAME        => static::ARG_ACTION,
                static::ARGUMENT_DESCRIPTION => "Action such as `$cache` or `$clear`.",
                static::ARGUMENT_MODE        => static::ARGUMENT_MODE__REQUIRED,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getOptions(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function execute(ContainerInterface $container, IoInterface $inOut)
    {
        $action    = $inOut->getArgument(static::ARG_ACTION);
        switch ($action) {
            case static::ACTION_CREATE_CACHE:
                (new self())->executeCache($container, $inOut);
                break;
            case static::ACTION_CLEAR_CACHE:
                (new self())->executeClear($container, $inOut);
                break;
            default:
                $inOut->writeError("Unsupported action `$action`." . PHP_EOL);
                break;
        }
    }

    /**
     * @param ContainerInterface $container
     * @param IoInterface        $inOut
     *
     * @return void
     */
    protected function executeClear(ContainerInterface $container, IoInterface $inOut)
    {
        assert($inOut);

        $appSettings    = $this->getApplicationSettings($container);
        $cacheDir       = $appSettings[ApplicationSettingsInterface::KEY_CACHE_FOLDER];
        $cacheCallable  = $appSettings[ApplicationSettingsInterface::KEY_CACHE_CALLABLE];
        list (, $class) = $this->parseCacheCallable($cacheCallable);

        if ($class === null) {
            // parsing of cache callable failed (most likely error in settings)
            throw new ConfigurationException();
        }

        $path = $cacheDir . DIRECTORY_SEPARATOR . $class . '.php';

        $this->createFileSystem($container)->delete($path);
    }

    /**
     * @param ContainerInterface $container
     * @param IoInterface        $inOut
     *
     * @return void
     */
    protected function executeCache(ContainerInterface $container, IoInterface $inOut)
    {
        assert($inOut);

        $appSettings   = $this->getApplicationSettings($container);
        $cacheDir      = $appSettings[ApplicationSettingsInterface::KEY_CACHE_FOLDER];
        $cacheCallable = $appSettings[ApplicationSettingsInterface::KEY_CACHE_CALLABLE];
        list ($namespace, $class, $method) = $this->parseCacheCallable($cacheCallable);
        if ($class === null || $namespace === null || $method === null) {
            // parsing of cache callable failed (most likely error in settings)
            throw new ConfigurationException();
        }

        $settingsProvider = $container->get(SettingsProviderInterface::class);
        assert($settingsProvider instanceof ArraySerializableInterface);
        $settingsData = $settingsProvider->serialize();
        $content      = $this->composeContent($settingsData, $namespace, $class, $method);

        $path = $cacheDir . DIRECTORY_SEPARATOR . $class . '.php';
        $this->createFileSystem($container)->write($path, $content);
    }
    /**
     * @param mixed $mightBeCallable
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function parseCacheCallable($mightBeCallable): array
    {
        if (is_string($mightBeCallable) === true &&
            count($nsClassMethod = explode('::', $mightBeCallable, 2)) === 2 &&
            count($nsClass = explode('\\', $nsClassMethod[0])) > 1
        ) {
            $canBeClass     = array_pop($nsClass);
            $canBeNamespace = array_filter($nsClass);
            $canBeMethod    = $nsClassMethod[1];
        } elseif (is_array($mightBeCallable) === true &&
            count($mightBeCallable) === 2 &&
            count($nsClass = explode('\\', $mightBeCallable[0])) > 1
        ) {
            $canBeClass     = array_pop($nsClass);
            $canBeNamespace = array_filter($nsClass);
            $canBeMethod    = $mightBeCallable[1];
        } else {
            return [null, null, null];
        }

        foreach (array_merge($canBeNamespace, [$canBeClass, $canBeMethod]) as $value) {
            // is string might have a-z, A-Z, _, numbers but has at least one a-z or A-Z.
            if (is_string($value) === false ||
                preg_match('/^\\w+$/i', $value) !== 1 ||
                preg_match('/^[a-z]+$/i', $value) !== 1
            ) {
                return [null, null, null];
            }
        }

        $namespace = implode('\\', $canBeNamespace);
        $class     = $canBeClass;
        $method    = $canBeMethod;

        return [$namespace, $class, $method];
    }

    /**
     * @param mixed  $value
     * @param string $className
     * @param string $methodName
     * @param string $namespace
     *
     * @return string
     */
    protected function composeContent(
        $value,
        string $namespace,
        string $className,
        string $methodName
    ): string {
        $now     = date(DATE_RFC2822);
        $data    = var_export($value, true);
        $content = <<<EOT
<?php namespace $namespace;

// THIS FILE IS AUTO GENERATED. DO NOT EDIT IT MANUALLY.
// Generated at: $now

class $className
{
    const DATA = $data;

    public static function $methodName()
    {
        return static::DATA;
    }
}

EOT;

        return $content;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return array
     */
    protected function getApplicationSettings(ContainerInterface $container): array
    {
        /** @var SettingsProviderInterface $settingsProvider */
        $settingsProvider = $container->get(SettingsProviderInterface::class);
        $appSettings      = $settingsProvider->get(ApplicationSettingsInterface::class);

        return $appSettings;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return FileSystemInterface
     */
    protected function createFileSystem(ContainerInterface $container): FileSystemInterface
    {
        return $container->get(FileSystemInterface::class);
    }
}
