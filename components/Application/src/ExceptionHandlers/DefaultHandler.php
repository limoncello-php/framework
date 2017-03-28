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

use ErrorException;
use Exception;
use Limoncello\Application\Providers\Application\ApplicationSettings as A;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Core\Contracts\Application\ExceptionHandlerInterface;
use Limoncello\Core\Contracts\Application\SapiInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\TextResponse;

/**
 * @package Limoncello\Application
 */
class DefaultHandler implements ExceptionHandlerInterface
{
    /**
     * @inheritdoc
     */
    public function handleException(Exception $exception, SapiInterface $sapi, ContainerInterface $container)
    {
        $this->handle($exception, $sapi, $container);
    }

    /**
     * @inheritdoc
     */
    public function handleThrowable(Throwable $throwable, SapiInterface $sapi, ContainerInterface $container)
    {
        $this->handle($throwable, $sapi, $container);
    }

    /**
     * @inheritdoc
     */
    public function handleFatal(array $error, ContainerInterface $container)
    {
        $errorException = new ErrorException($error['message'], $error['type'], 1, $error['file'], $error['line']);
        $this->logException($errorException, $container, 'Fatal error');
    }

    /**
     * @param Exception|Throwable $exception
     * @param SapiInterface       $sapi
     * @param ContainerInterface  $container
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function handle($exception, SapiInterface $sapi, ContainerInterface $container)
    {
        $message  = 'Internal Server Error';

        $this->logException($exception, $container, $message);

        list($isDebug, $appName, $exceptionDumper) = $this->getSettings($container);

        if ($isDebug === true) {
            $run     = new Run();
            $handler = new PrettyPageHandler();

            if ($exceptionDumper !== null) {
                $appSpecificDetails = call_user_func($exceptionDumper, $exception, $container);
                $handler->addDataTable("$appName Details", $appSpecificDetails);
            }

            $handler->setPageTitle("Whoops! There was a problem with '$appName'.");
            $run->pushHandler($handler);

            $htmlMessage = $run->handleException($exception);
            $response    = new HtmlResponse($htmlMessage, 500);
        } else {
            $response = new TextResponse($message, 500);
        }

        $sapi->handleResponse($response);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return array
     */
    private function getSettings(ContainerInterface $container): array
    {
        /** @var SettingsProviderInterface $settingsProvider */
        if ($container->has(SettingsProviderInterface::class) === true &&
            ($settingsProvider = $container->get(SettingsProviderInterface::class)) !== null &&
            $settingsProvider->has(A::class) === true
        ) {
            $appSettings = $settingsProvider->get(A::class);

            return [
                $appSettings[A::KEY_IS_DEBUG],
                $appSettings[A::KEY_APP_NAME],
                $appSettings[A::KEY_EXCEPTION_DUMPER] ?? null,
            ];
        }

        return [false, null, null];
    }

    /**
     * @param Exception          $exception
     * @param ContainerInterface $container
     * @param string             $message
     *
     * @return void
     */
    private function logException(Exception $exception, ContainerInterface $container, $message)
    {
        if ($container->has(LoggerInterface::class) === true) {
            /** @var LoggerInterface $logger */
            $logger = $container->get(LoggerInterface::class);
            $logger->critical($message, ['exception' => $exception]);
        }
    }
}
