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
use Limoncello\Application\Packages\Application\ApplicationSettings as A;
use Limoncello\Contracts\Core\SapiInterface;
use Limoncello\Contracts\Exceptions\ExceptionHandlerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\TextResponse;

/**
 * @package Limoncello\Application
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DefaultHandler implements ExceptionHandlerInterface
{
    /**
     * @inheritdoc
     */
    public function handleException(Exception $exception, SapiInterface $sapi, ContainerInterface $container): void
    {
        $this->handle($exception, $sapi, $container);
    }

    /**
     * @inheritdoc
     */
    public function handleThrowable(Throwable $throwable, SapiInterface $sapi, ContainerInterface $container): void
    {
        $this->handle($throwable, $sapi, $container);
    }

    /**
     * @inheritdoc
     */
    public function handleFatal(array $error, ContainerInterface $container): void
    {
        $errorException = new ErrorException($error['message'], $error['type'], 1, $error['file'], $error['line']);
        $this->logException($errorException, $container, 'Fatal error');
    }

    /**
     * @param Throwable           $exception
     * @param SapiInterface       $sapi
     * @param ContainerInterface  $container
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function handle(Throwable $exception, SapiInterface $sapi, ContainerInterface $container): void
    {
        $message  = 'Internal Server Error';

        $this->logException($exception, $container, $message);

        list($isDebug, $appName, $exceptionDumper) = $this->getSettings($container);

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
     * @param Throwable          $exception
     * @param ContainerInterface $container
     * @param string             $message
     *
     * @return void
     */
    private function logException(Throwable $exception, ContainerInterface $container, string $message): void
    {
        if ($container->has(LoggerInterface::class) === true) {
            /** @var LoggerInterface $logger */
            $logger = $container->get(LoggerInterface::class);
            $logger->critical($message, ['exception' => $exception]);
        }
    }
}
