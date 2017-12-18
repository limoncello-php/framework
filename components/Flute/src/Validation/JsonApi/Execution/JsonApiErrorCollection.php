<?php namespace Limoncello\Flute\Validation\JsonApi\Execution;

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

use Limoncello\Container\Traits\HasContainerTrait;
use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\Flute\Contracts\Validation\ErrorCodes;
use Limoncello\Flute\Http\JsonApiResponse;
use Limoncello\Flute\Validation\Traits\HasValidationFormatterTrait;
use Limoncello\Validation\Contracts\Errors\ErrorInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @package Limoncello\Flute
 */
class JsonApiErrorCollection extends ErrorCollection
{
    use HasContainerTrait, HasValidationFormatterTrait;

    /**
     * @var int
     */
    private $errorStatus;

    /**
     * @var FormatterInterface|null
     */
    private $messageFormatter;

    /**
     * @param ContainerInterface $container
     * @param int                $errorStatus
     */
    public function __construct(
        ContainerInterface $container,
        int $errorStatus = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY
    ) {
        $this->setContainer($container);
        $this->errorStatus = $errorStatus;
    }

    /**
     * @inheritdoc
     */
    public function addValidationAttributeError(ErrorInterface $error)
    {
        $title  = $this->getInvalidValueMessage();
        $detail = $this->getValidationMessage($error);
        $this->addDataAttributeError($error->getParameterName(), $title, $detail, $this->getErrorStatus());
    }

    /**
     * @inheritdoc
     */
    public function addValidationRelationshipError(ErrorInterface $error)
    {
        $title  = $this->getInvalidValueMessage();
        $detail = $this->getValidationMessage($error);
        $this->addRelationshipError($error->getParameterName(), $title, $detail, $this->getErrorStatus());
    }

    /**
     * @return int
     */
    protected function getErrorStatus(): int
    {
        return $this->errorStatus;
    }

    /**
     * @return string
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getInvalidValueMessage(): string
    {
        $message = $this->getMessageFormatter()->formatMessage(ErrorCodes::INVALID_VALUE);

        return $message;
    }

    /**
     * @param ErrorInterface $error
     *
     * @return string
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getValidationMessage(ErrorInterface $error): string
    {
        $context = $error->getMessageContext();
        $args    = $context === null ? [] : $context;
        $message = $this->getMessageFormatter()->formatMessage($error->getMessageCode(), $args);

        return $message;
    }

    /**
     * @return FormatterInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getMessageFormatter(): FormatterInterface
    {
        if ($this->messageFormatter === null) {
            $this->messageFormatter = $this->createValidationFormatter();
        }

        return $this->messageFormatter;
    }
}
