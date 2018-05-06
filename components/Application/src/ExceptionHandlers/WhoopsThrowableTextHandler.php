<?php namespace Limoncello\Application\ExceptionHandlers;

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

use Exception;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface as A;
use Limoncello\Contracts\Application\CacheSettingsProviderInterface;
use Limoncello\Contracts\Exceptions\ThrowableHandlerInterface;
use Limoncello\Contracts\Http\ThrowableResponseInterface;
use Limoncello\Core\Application\ThrowableResponseTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Whoops\Handler\PlainTextHandler;
use Whoops\Run;
use Zend\Diactoros\Response\TextResponse;

/**
 * @package Limoncello\Application
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class WhoopsThrowableTextHandler implements ThrowableHandlerInterface
{
    /** Default HTTP code. */
    protected const DEFAULT_HTTP_ERROR_CODE = 500;

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function createResponse(Throwable $throwable, ContainerInterface $container): ThrowableResponseInterface
    {
        $message = 'Internal Server Error';

        $this->logException($throwable, $container, $message);

        $isDebug = $this->isDebug($container);
        if ($isDebug === true) {
            $run = new Run();

            // If these two options are not used it would work fine with PHP Unit and XDebug,
            // however it produces output to console under PhpDbg. So we need a couple of
            // tweaks to make it work predictably in both environments.
            //
            // this one forbids Whoops spilling output to console
            $run->writeToOutput(false);
            // by default after sending error to output Whoops stops execution
            // as we want just generated output `string` we instruct not to halt
            $run->allowQuit(false);

            $handler = new PlainTextHandler();
            $handler->setException($throwable);
            $run->pushHandler($handler);

            $text     = $run->handleException($throwable);
            $response = $this->createThrowableTextResponse($throwable, $text, static::DEFAULT_HTTP_ERROR_CODE);
        } else {
            $response = $this->createThrowableTextResponse($throwable, $message, static::DEFAULT_HTTP_ERROR_CODE);
        }

        return $response;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return bool
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function isDebug(ContainerInterface $container): bool
    {
        $appConfig = null;

        /** @var CacheSettingsProviderInterface $settingsProvider */
        if ($container->has(CacheSettingsProviderInterface::class) === true &&
            ($settingsProvider = $container->get(CacheSettingsProviderInterface::class)) !== null
        ) {
            $appConfig = $settingsProvider->getApplicationConfiguration();
        }

        return $appConfig[A::KEY_IS_DEBUG] ?? false;
    }

    /**
     * @param Throwable          $exception
     * @param ContainerInterface $container
     * @param string             $message
     *
     * @return void
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function logException(Throwable $exception, ContainerInterface $container, string $message): void
    {
        if ($container->has(LoggerInterface::class) === true) {
            /** @var LoggerInterface $logger */
            $logger = $container->get(LoggerInterface::class);

            // The sad truth is that when you have a problem logging might not be available (e.g. no permissions
            // to write on a disk). We can't do much with it and can only hope that the error information will be
            // delivered to the user other way.
            try {
                $logger->critical($message, ['exception' => $exception]);
            } catch (Exception $secondException) {
            }
        }
    }

    /**
     * @param Throwable $throwable
     * @param string    $text
     * @param int       $status
     *
     * @return ThrowableResponseInterface
     */
    private function createThrowableTextResponse(
        Throwable $throwable,
        string $text,
        int $status
    ): ThrowableResponseInterface {
        return new class ($throwable, $text, $status) extends TextResponse implements ThrowableResponseInterface
        {
            use ThrowableResponseTrait;

            /**
             * @param Throwable $throwable
             * @param string    $text
             * @param int       $status
             */
            public function __construct(Throwable $throwable, string $text, int $status)
            {
                parent::__construct($text, $status);
                $this->setThrowable($throwable);
            }
        };
    }
}
