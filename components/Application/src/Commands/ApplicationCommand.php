<?php declare(strict_types=1);

namespace Limoncello\Application\Commands;

/**
 * Copyright 2015-2020 info@neomerx.com
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
use Limoncello\Contracts\Application\ApplicationConfigurationInterface;
use Limoncello\Contracts\Application\CacheSettingsProviderInterface;
use Limoncello\Contracts\Commands\CommandInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\FileSystem\FileSystemInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionMethod;
use function array_filter;
use function array_pop;
use function assert;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_string;
use function preg_match;

/**
 * @package Limoncello\Application
 */
class ApplicationCommand implements CommandInterface
{
    /**
     * Command name.
     */
    const NAME = 'l:app';

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
        return static::NAME;
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
    public static function execute(ContainerInterface $container, IoInterface $inOut): void
    {
        (new static())->run($container, $inOut);
    }

    /**
     * @param ContainerInterface $container
     * @param IoInterface $inOut
     *
     * @return void
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function run(ContainerInterface $container, IoInterface $inOut): void
    {
        $action = $inOut->getArgument(static::ARG_ACTION);
        switch ($action) {
            case static::ACTION_CREATE_CACHE:
                $this->executeCache($container, $inOut);
                break;
            case static::ACTION_CLEAR_CACHE:
                $this->executeClear($container, $inOut);
                break;
            default:
                $inOut->writeError("Unsupported action `$action`." . PHP_EOL);
                break;
        }
    }

    /**
     * @param ContainerInterface $container
     * @param IoInterface $inOut
     *
     * @return void
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function executeClear(ContainerInterface $container, IoInterface $inOut): void
    {
        assert($inOut);

        $appConfig     = $this->getApplicationConfiguration($container);
        $cacheCallable = $appConfig[ApplicationConfigurationInterface::KEY_CACHE_CALLABLE];
        assert(is_string($cacheCallable));

        // if exists
        if (is_callable($cacheCallable) === true) {
            // location of the cache file
            $path = (new ReflectionMethod($cacheCallable))->getDeclaringClass()->getFileName();

            $fileSystem = $this->getFileSystem($container);
            if ($fileSystem->exists($path) === true) {
                $fileSystem->delete($path);
                $inOut->writeInfo("Cache file deleted `$path`." . PHP_EOL, IoInterface::VERBOSITY_VERBOSE);

                return;
            }
        }

        $inOut->writeInfo('Cache already clean.' . PHP_EOL);
    }

    /**
     * @param ContainerInterface $container
     * @param IoInterface        $inOut
     *
     * @return void
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function executeCache(ContainerInterface $container, IoInterface $inOut): void
    {
        assert($inOut);

        $appConfig     = $this->getApplicationConfiguration($container);
        $cacheDir      = $appConfig[ApplicationConfigurationInterface::KEY_CACHE_FOLDER];
        $cacheCallable = $appConfig[ApplicationConfigurationInterface::KEY_CACHE_CALLABLE];
        list ($namespace, $class, $method) = $this->parseCacheCallable($cacheCallable);
        if ($class === null || $namespace === null || $method === null) {
            // parsing of cache callable failed (most likely error in settings)
            throw new ConfigurationException();
        }

        /** @var CacheSettingsProviderInterface $settingsProvider */
        $settingsProvider = $container->get(CacheSettingsProviderInterface::class);
        $settingsData     = $settingsProvider->serialize();
        $content          = $this->composeContent($settingsData, $namespace, $class, $method);

        $path = $cacheDir . DIRECTORY_SEPARATOR . $class . '.php';
        $this->getFileSystem($container)->write($path, $content);

        $inOut->writeInfo('Cache created.' . PHP_EOL);
        $inOut->writeInfo("Cache written to `$path`." . PHP_EOL, IoInterface::VERBOSITY_VERBOSE);
    }

    /**
     * @param mixed $mightBeCallable
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.IfStatementAssignment)
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
        $now  = date(DATE_RFC2822);
        $data = var_export($value, true);

        assert(
            $data !== null,
            'It seems the data are not exportable. It is likely to be caused by class instances ' .
            'that do not implement ` __set_state` magic method required by `var_export`. ' .
            'See http://php.net/manual/en/language.oop5.magic.php#object.set-state for more details.'
        );

        $content = <<<EOT
<?php declare(strict_types=1);

namespace $namespace;

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
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getApplicationConfiguration(ContainerInterface $container): array
    {
        /** @var CacheSettingsProviderInterface $settingsProvider */
        $settingsProvider = $container->get(CacheSettingsProviderInterface::class);
        $appConfig        = $settingsProvider->getApplicationConfiguration();

        return $appConfig;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return FileSystemInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getFileSystem(ContainerInterface $container): FileSystemInterface
    {
        return $container->get(FileSystemInterface::class);
    }
}
