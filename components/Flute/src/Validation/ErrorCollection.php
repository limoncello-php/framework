<?php namespace Limoncello\Flute\Validation;

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

use Limoncello\Flute\Contracts\I18n\TranslatorInterface as T;
use Limoncello\Flute\Http\JsonApiResponse;
use Limoncello\Validation\Contracts\TranslatorInterface as ValidationTranslatorInterface;
use Limoncello\Validation\Errors\Error;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ErrorCollection extends \Neomerx\JsonApi\Exceptions\ErrorCollection
{
    /**
     * @var T
     */
    private $jsonApiTranslator;

    /**
     * @var ValidationTranslatorInterface
     */
    private $validationTranslator;

    /**
     * @var int
     */
    private $errorStatus;

    /**
     * @var null|string
     */
    private $msgInvalidElement;

    /**
     * @param T                             $jsonApiTranslator
     * @param ValidationTranslatorInterface $validationTranslator
     * @param int                           $errorStatus
     */
    public function __construct(
        T $jsonApiTranslator,
        ValidationTranslatorInterface $validationTranslator,
        $errorStatus = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY
    ) {
        $this->jsonApiTranslator    = $jsonApiTranslator;
        $this->validationTranslator = $validationTranslator;
        $this->errorStatus          = $errorStatus;
    }

    /**
     * @inheritdoc
     */
    public function addValidationTypeError(Error $error)
    {
        $detail = $this->getValidationTranslator()->translate($error);
        $this->addDataTypeError($this->getInvalidElementMessage(), $detail, $this->getErrorStatus());
    }

    /**
     * @inheritdoc
     */
    public function addValidationIdError(Error $error)
    {
        $detail = $this->getValidationTranslator()->translate($error);
        $this->addDataIdError($this->getInvalidElementMessage(), $detail, $this->getErrorStatus());
    }

    /**
     * @inheritdoc
     */
    public function addValidationAttributeError(Error $error)
    {
        $title  = $this->getInvalidElementMessage();
        $detail = $this->getValidationTranslator()->translate($error);
        $this->addDataAttributeError($error->getParameterName(), $title, $detail, $this->getErrorStatus());
    }

    /**
     * @inheritdoc
     */
    public function addValidationRelationshipError(Error $error)
    {
        $title  = $this->getInvalidElementMessage();
        $detail = $this->getValidationTranslator()->translate($error);
        $this->addRelationshipError($error->getParameterName(), $title, $detail, $this->getErrorStatus());
    }

    /**
     * @return T
     */
    protected function getJsonApiTranslator()
    {
        return $this->jsonApiTranslator;
    }

    /**
     * @return ValidationTranslatorInterface
     */
    protected function getValidationTranslator()
    {
        return $this->validationTranslator;
    }

    /**
     * @return int
     */
    protected function getErrorStatus()
    {
        return $this->errorStatus;
    }

    /**
     * @return string
     */
    private function getInvalidElementMessage()
    {
        if ($this->msgInvalidElement === null) {
            $this->msgInvalidElement = $this->getJsonApiTranslator()->get(T::MSG_ERR_INVALID_ELEMENT);
        }

        return $this->msgInvalidElement;
    }
}
