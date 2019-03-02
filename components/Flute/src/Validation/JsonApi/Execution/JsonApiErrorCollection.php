<?php declare (strict_types = 1);

namespace Limoncello\Flute\Validation\JsonApi\Execution;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\Flute\Contracts\Validation\ErrorCodes;
use Limoncello\Flute\Http\JsonApiResponse;
use Limoncello\Flute\L10n\Validation;
use Limoncello\Validation\Contracts\Errors\ErrorInterface;
use Neomerx\JsonApi\Schema\ErrorCollection;

/**
 * @package Limoncello\Flute
 */
class JsonApiErrorCollection extends ErrorCollection
{
    /**
     * @var FormatterInterface
     */
    private $messageFormatter;

    /**
     * @param FormatterInterface $formatter
     */
    public function __construct(FormatterInterface $formatter)
    {
        $this->messageFormatter = $formatter;
    }

    /**
     * @inheritdoc
     */
    public function addValidationIdError(
        ErrorInterface $error,
        int $errorStatus = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY
    ): void {
        $title  = $this->getInvalidValueMessage();
        $detail = $this->getValidationMessage($error);
        $this->addDataIdError($title, $detail, (string)$errorStatus);
    }

    /**
     * @inheritdoc
     */
    public function addValidationTypeError(
        ErrorInterface $error,
        int $errorStatus = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY
    ): void {
        $title  = $this->getInvalidValueMessage();
        $detail = $this->getValidationMessage($error);
        $this->addDataTypeError($title, $detail, (string)$errorStatus);
    }

    /**
     * @inheritdoc
     */
    public function addValidationAttributeError(
        ErrorInterface $error,
        int $errorStatus = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY
    ): void {
        $title  = $this->getInvalidValueMessage();
        $detail = $this->getValidationMessage($error);
        $this->addDataAttributeError($error->getParameterName(), $title, $detail, (string)$errorStatus);
    }

    /**
     * @inheritdoc
     */
    public function addValidationRelationshipError(
        ErrorInterface $error,
        int $errorStatus = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY
    ): void {
        $title  = $this->getInvalidValueMessage();
        $detail = $this->getValidationMessage($error);
        $this->addRelationshipError($error->getParameterName(), $title, $detail, (string)$errorStatus);
    }

    /**
     * @inheritdoc
     */
    public function addValidationQueryError(
        string $paramName,
        ErrorInterface $error,
        int $errorStatus = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY
    ): void {
        $title  = $this->getInvalidValueMessage();
        $detail = $this->getValidationMessage($error);
        $this->addQueryParameterError($paramName, $title, $detail, (string)$errorStatus);
    }

    /**
     *
     * @return string
     */
    private function getInvalidValueMessage(): string
    {
        $defaultMessage = Validation::convertCodeToDefaultMessage(ErrorCodes::INVALID_VALUE);
        $message        = $this->getMessageFormatter()->formatMessage($defaultMessage);

        return $message;
    }

    /**
     * @param ErrorInterface $error
     *
     * @return string
     */
    private function getValidationMessage(ErrorInterface $error): string
    {
        $context        = $error->getMessageContext();
        $args           = $context === null ? [] : $context;
        $defaultMessage = Validation::convertCodeToDefaultMessage($error->getMessageCode());
        $message        = $this->getMessageFormatter()->formatMessage($defaultMessage, $args);

        return $message;
    }

    /**
     * @return FormatterInterface
     */
    private function getMessageFormatter(): FormatterInterface
    {
        return $this->messageFormatter;
    }
}
