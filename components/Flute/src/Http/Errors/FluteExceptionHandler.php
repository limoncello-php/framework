<?php namespace Limoncello\Flute\Http\Errors;

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
use Limoncello\Contracts\Application\ApplicationSettingsInterface as S;
use Limoncello\Contracts\Core\SapiInterface;
use Limoncello\Contracts\Exceptions\ExceptionHandlerInterface;
use Limoncello\Contracts\Http\Cors\CorsStorageInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Http\JsonApiResponse;
use Neomerx\JsonApi\Document\Error;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FluteExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * The following error classes (Exceptions and Throwables) will not be logged.
     *
     * @var string[]
     */
    private static $ignoredErrorClasses = [
        JsonApiException::class,
    ];

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
        $this->logError($errorException, $container, 'Fatal error');
    }

    /**
     * @param Exception|Throwable $error
     * @param SapiInterface       $sapi
     * @param ContainerInterface  $container
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function handle($error, SapiInterface $sapi, ContainerInterface $container)
    {
        $message = 'Internal Server Error';

        $this->logError($error, $container, $message);

        // compose JSON API Error with appropriate level of details
        if ($error instanceof JsonApiException) {
            /** @var JsonApiException $error */
            $errors   = $error->getErrors();
            $httpCode = $error->getHttpCode();
        } else {
            // we assume that 'normal' should be JsonApiException so anything else is 500 error code
            $httpCode = 500;
            $details  = null;
            $settings = $container->get(SettingsProviderInterface::class)->get(S::class);
            if ($settings[S::KEY_IS_DEBUG] === true) {
                $message = $error->getMessage();
                $details = (string)$error;
            }
            $errors = new ErrorCollection();
            $errors->add(new Error(null, null, $httpCode, null, $message, $details));
        }

        // encode the error and send to client
        /** @var EncoderInterface $encoder */
        $encoder     = $container->get(EncoderInterface::class);
        $content     = $encoder->encodeErrors($errors);
        /** @var CorsStorageInterface $corsStorage */
        $corsStorage = $container->get(CorsStorageInterface::class);
        $response    = new JsonApiResponse($content, $httpCode, $corsStorage->getHeaders());
        $sapi->handleResponse($response);
    }

    /**
     * @param Exception|Throwable $error
     * @param ContainerInterface  $container
     * @param string              $message
     *
     * @return void
     */
    private function logError($error, ContainerInterface $container, $message)
    {
        if (in_array(get_class($error), static::$ignoredErrorClasses) === false &&
            $container->has(LoggerInterface::class) === true
        ) {
            /** @var LoggerInterface $logger */
            $logger = $container->get(LoggerInterface::class);
            $logger->critical($message, ['error' => $error]);
        }
    }
}
