<?php namespace Limoncello\Commands;

/**
 * Copyright 2015-2018 info@neomerx.com
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
use Composer\Command\BaseCommand;
use Exception;
use Limoncello\Commands\Traits\CommandSerializationTrait;
use Limoncello\Commands\Traits\CommandTrait;
use Limoncello\Commands\Wrappers\DataArgumentWrapper;
use Limoncello\Commands\Wrappers\DataOptionWrapper;
use Limoncello\Common\Reflection\CheckCallableTrait;
use Limoncello\Common\Reflection\ClassIsTrait;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface;
use Limoncello\Contracts\Application\CacheSettingsProviderInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\Commands\RoutesConfiguratorInterface;
use Limoncello\Contracts\Commands\RoutesInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Exceptions\ThrowableHandlerInterface;
use Limoncello\Contracts\FileSystem\FileSystemInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Limoncello\Commands
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LimoncelloCommand extends BaseCommand
{
    use CommandTrait, CommandSerializationTrait, ClassIsTrait;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $help;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @var array
     */
    private $options;

    /**
     * @var callable|array
     */
    private $callable;

    /**
     * @param string $name
     * @param string $description
     * @param string $help
     * @param array  $arguments
     * @param array  $options
     * @param array  $callable
     */
    public function __construct(
        string $name,
        string $description,
        string $help,
        array $arguments,
        array $options,
        array $callable
    ) {
        $this->description = $description;
        $this->help        = $help;
        $this->arguments   = $arguments;
        $this->options     = $options;
        $this->callable    = $callable;

        // it is important to call the parent constructor after
        // data init as it calls `configure` method.
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    public function configure()
    {
        parent::configure();

        $this
            ->setDescription($this->description)
            ->setHelp($this->help);

        foreach ($this->arguments as $data) {
            $arg = new DataArgumentWrapper($data);
            $this->addArgument($arg->getName(), $arg->getMode(), $arg->getDescription(), $arg->getDefault());
        }

        foreach ($this->options as $data) {
            $opt = new DataOptionWrapper($data);
            $this->addOption(
                $opt->getName(),
                $opt->getShortcut(),
                $opt->getMode(),
                $opt->getDescription(),
                $opt->getDefault()
            );
        }
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @inheritdoc
     *
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // This method does bootstrap for every command (e.g. configure containers)
        // and then calls the actual command handler.

        $container =  null;

        try {
            $container = $this->createContainer($this->getComposer());
            assert($container instanceof LimoncelloContainerInterface);

            // At this point we have probably only partly configured container and we need to read from it
            // CLI route setting in order to fully configure it and then run the command with middleware.
            // However, when we read anything from it, it changes its state so we are not allowed to add
            // anything to it (technically we can but in some cases it might cause an exception).
            // So, what's the solution? We clone the container, read from the clone everything we need,
            // and then continue with the original unchanged container.
            $routesFolder = null;
            if (true) {
                $containerClone = clone $container;

                /** @var CacheSettingsProviderInterface $provider */
                $provider  = $container->get(CacheSettingsProviderInterface::class);
                $appConfig = $provider->getApplicationConfiguration();

                $routesFolder = $appConfig[ApplicationConfigurationInterface::KEY_ROUTES_FOLDER] ?? null;

                /** @var FileSystemInterface $files */
                assert(
                    ($files = $containerClone->get(FileSystemInterface::class)) !== null &&
                    $routesFolder !== null && $files->exists($routesFolder) === true,
                    'Routes folder must be defined in application settings.'
                );

                unset($containerClone);
            }

            [$configurators, $middleware] =
                $this->readExtraContainerConfiguratorsAndMiddleware($routesFolder, $this->getName());

            $this->executeContainerConfigurators($configurators, $container);

            $handler = $this->buildExecutionChain($middleware, $this->callable, $container);

            // finally go through all middleware and execute command handler
            // (container has to be the same (do not send as param), but middleware my wrap IO (send as param)).
            call_user_func($handler, $this->wrapIo($input, $output));
        } catch (Exception $exception) {
            if ($container !== null && $container->has(ThrowableHandlerInterface::class) === true) {
                /** @var ThrowableHandlerInterface $handler */
                $handler  = $container->get(ThrowableHandlerInterface::class);
                $response = $handler->createResponse($exception, $container);

                $output->writeln((string)$response->getBody());
            } else {
                $message = $exception->getMessage();
                $file    = $exception->getFile();
                $line    = $exception->getLine();
                $trace   = $exception->getTraceAsString();

                $output->writeln("$message at $file#$line" . PHP_EOL . $trace);
            }

            throw $exception;
        }
    }

    /**
     * @param string $routesFolder
     * @param string $commandName
     *
     * @return array
     *
     * @throws ReflectionException
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function readExtraContainerConfiguratorsAndMiddleware(string $routesFolder, string $commandName): array
    {
        $routesFilter = new class ($commandName) implements RoutesInterface
        {
            use CheckCallableTrait;

            /** @var array */
            private $middleware = [];

            /** @var array */
            private $configurators = [];

            /** @var string */
            private $commandName;

            /**
             * @param string $commandName
             */
            public function __construct(string $commandName)
            {
                $this->commandName = $commandName;
            }

            /**
             * @inheritdoc
             */
            public function addGlobalMiddleware(array $middleware): RoutesInterface
            {
                assert($this->checkMiddlewareCallables($middleware) === true);

                $this->middleware = array_merge($this->middleware, $middleware);

                return $this;
            }

            /**
             * @inheritdoc
             */
            public function addGlobalContainerConfigurators(array $configurators): RoutesInterface
            {
                assert($this->checkConfiguratorCallables($configurators) === true);

                $this->configurators = array_merge($this->configurators, $configurators);

                return $this;
            }

            /**
             * @inheritdoc
             */
            public function addCommandMiddleware(string $name, array $middleware): RoutesInterface
            {
                assert($this->checkMiddlewareCallables($middleware) === true);

                if ($this->commandName === $name) {
                    $this->middleware = array_merge($this->middleware, $middleware);
                }

                return $this;
            }

            /**
             * @inheritdoc
             */
            public function addCommandContainerConfigurators(string $name, array $configurators): RoutesInterface
            {
                assert($this->checkConfiguratorCallables($configurators) === true);

                if ($this->commandName === $name) {
                    $this->configurators = array_merge($this->configurators, $configurators);
                }

                return $this;
            }

            /**
             * @return array
             */
            public function getMiddleware(): array
            {
                return $this->middleware;
            }

            /**
             * @return array
             */
            public function getConfigurators(): array
            {
                return $this->configurators;
            }

            /**
             * @param array $mightBeConfigurators
             *
             * @return bool
             */
            private function checkConfiguratorCallables(array $mightBeConfigurators): bool
            {
                $result = true;

                foreach ($mightBeConfigurators as $mightBeCallable) {
                    $result = $result === true &&
                        $this->checkPublicStaticCallable(
                            $mightBeCallable,
                            [LimoncelloContainerInterface::class],
                            'void'
                        );
                }

                return $result;
            }

            /**
             * @param array $mightBeMiddleware
             *
             * @return bool
             */
            private function checkMiddlewareCallables(array $mightBeMiddleware): bool
            {
                $result = true;

                foreach ($mightBeMiddleware as $mightBeCallable) {
                    $result = $result === true && $this->checkPublicStaticCallable(
                        $mightBeCallable,
                        [IoInterface::class, Closure::class, PsrContainerInterface::class],
                        'void'
                    );
                }

                return $result;
            }
        };

        foreach (static::selectClasses($routesFolder, RoutesConfiguratorInterface::class) as $class) {
            /** @var RoutesConfiguratorInterface $class */
            $class::configureRoutes($routesFilter);
        }

        return [$routesFilter->getConfigurators(), $routesFilter->getMiddleware()];
    }

    /**
     * @param callable[]                   $configurators
     * @param LimoncelloContainerInterface $container
     *
     * @return void
     */
    private function executeContainerConfigurators(array $configurators, LimoncelloContainerInterface $container): void
    {
        foreach ($configurators as $configurator) {
            call_user_func($configurator, $container);
        }
    }

    /**
     * @param array                 $middleware
     * @param callable              $command
     * @param PsrContainerInterface $container
     *
     * @return Closure
     */
    private function buildExecutionChain(
        array $middleware,
        callable $command,
        PsrContainerInterface $container
    ): Closure {
        $next = function (IoInterface $inOut) use ($command, $container): void {
            call_user_func($command, $container, $inOut);
        };

        for ($index = count($middleware) - 1; $index >= 0; $index--) {
            $currentMiddleware = $middleware[$index];
            $next = function (IoInterface $inOut) use ($currentMiddleware, $next, $container): void {
                call_user_func($currentMiddleware, $inOut, $next, $container);
            };
        }

        return $next;
    }
}
