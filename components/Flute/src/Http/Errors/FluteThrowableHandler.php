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

use Exception;
use Limoncello\Contracts\Exceptions\ThrowableHandlerInterface;
use Limoncello\Contracts\Http\ThrowableResponseInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Http\JsonApiResponse;
use Neomerx\JsonApi\Document\Error;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FluteThrowableHandler implements ThrowableHandlerInterface
{
    use LoggerAwareTrait;

    /**
     * Those classes will not be logged. Note that classes are expected to be keys but not values.
     *
     * @var array
     */
    private $doNotLogClassesAsKeys;

    /**
     * @var int
     */
    private $httpCodeForUnexpected;

    /**
     * @var bool
     */
    private $isDebug;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @param EncoderInterface $encoder
     * @param array            $doNotLogClassesAsKeys
     * @param int              $httpCodeForUnexpected
     * @param bool             $isDebug
     */
    public function __construct(
        EncoderInterface $encoder,
        array $doNotLogClassesAsKeys,
        int $httpCodeForUnexpected,
        bool $isDebug
    ) {
        $this->doNotLogClassesAsKeys = $doNotLogClassesAsKeys;
        $this->httpCodeForUnexpected = $httpCodeForUnexpected;
        $this->isDebug               = $isDebug;
        $this->encoder               = $encoder;
    }

    /**
     * @inheritdoc
     */
    public function createResponse(Throwable $throwable, ContainerInterface $container): ThrowableResponseInterface
    {
        unset($container);

        $message = 'Internal Server Error';

        $this->logError($throwable, $message);

        // compose JSON API Error with appropriate level of details
        if ($throwable instanceof JsonApiException) {
            /** @var JsonApiException $throwable */
            $errors   = $throwable->getErrors();
            $httpCode = $throwable->getHttpCode();
        } else {
            $errors   = new ErrorCollection();
            $httpCode = $this->getHttpCodeForUnexpectedThrowable();
            $details  = null;
            if ($this->isDebug === true) {
                $message = $throwable->getMessage();
                $details = (string)$throwable;
            }
            $errors->add(new Error(null, null, $httpCode, null, $message, $details));
        }

        // encode the error and send to client
        $content = $this->encoder->encodeErrors($errors);

        return $this->createThrowableJsonApiResponse($throwable, $content, $httpCode);
    }

    /**
     * @param Throwable $throwable
     * @param string    $message
     *
     * @return void
     */
    private function logError(Throwable $throwable, string $message): void
    {
        if ($this->logger !== null && $this->shouldBeLogged($throwable) === true) {
            // on error (e.g. no permission to write on disk or etc) ignore
            try {
                $this->logger->error($message, ['error' => $throwable]);
            } catch (Exception $exception) {
            }
        }
    }

    /**
     * @return int
     */
    private function getHttpCodeForUnexpectedThrowable(): int
    {
        return $this->httpCodeForUnexpected;
    }

    /**
     * @param Throwable $throwable
     *
     * @return bool
     */
    private function shouldBeLogged(Throwable $throwable): bool
    {
        $result = array_key_exists(get_class($throwable), $this->doNotLogClassesAsKeys) === false;

        return $result;
    }

    /**
     * @param Throwable $throwable
     * @param string    $content
     * @param int       $status
     *
     * @return ThrowableResponseInterface
     */
    private function createThrowableJsonApiResponse(Throwable $throwable, string $content, int $status): ThrowableResponseInterface
    {
        return new class ($throwable, $content, $status) extends JsonApiResponse implements ThrowableResponseInterface
        {
            /**
             * @var Throwable
             */
            private $throwable;

            /**
             * @param Throwable $throwable
             * @param string    $content
             * @param int       $status
             */
            public function __construct(Throwable $throwable, string $content, int $status)
            {
                parent::__construct($content, $status);
                $this->throwable = $throwable;
            }

            /**
             * @return Throwable
             */
            public function getThrowable(): Throwable
            {
                return $this->throwable;
            }
        };
    }
}
