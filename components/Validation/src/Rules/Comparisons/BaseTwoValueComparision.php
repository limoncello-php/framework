<?php declare(strict_types=1);

namespace Limoncello\Validation\Rules\Comparisons;

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

use Limoncello\Validation\Contracts\Blocks\ExecutionBlockInterface;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Contracts\Rules\ComparisionInterface;
use Limoncello\Validation\Rules\BaseRule;
use Limoncello\Validation\Rules\Generic\Fail;
use Limoncello\Validation\Rules\Generic\IfOperator;
use Limoncello\Validation\Rules\Generic\Success;

/**
 * @package Limoncello\Validation
 */
abstract class BaseTwoValueComparision extends BaseRule implements ComparisionInterface
{
    /**
     * Property key.
     */
    const PROPERTY_LOWER_VALUE = self::PROPERTY_LAST + 1;

    /**
     * Property key.
     */
    const PROPERTY_UPPER_VALUE = self::PROPERTY_LOWER_VALUE + 1;

    /**
     * Property key.
     */
    const PROPERTY_TWO_VALUE_LAST = self::PROPERTY_UPPER_VALUE;

    /**
     * @var mixed
     */
    private $lowerValue;

    /**
     * @var mixed
     */
    private $upperValue;

    /**
     * @var int
     */
    private $errorCode;

    /**
     * @var string
     */
    private $messageTemplate;

    /**
     * @var array
     */
    private $messageParams;

    /**
     * @param mixed  $lowerValue
     * @param mixed  $upperValue
     * @param int    $errorCode
     * @param string $messageTemplate
     * @param array  $messageParams
     */
    public function __construct($lowerValue, $upperValue, int $errorCode, string $messageTemplate, array $messageParams)
    {
        assert($this->checkEachValueConvertibleToString($messageParams));

        $this->lowerValue      = $lowerValue;
        $this->upperValue      = $upperValue;
        $this->errorCode       = $errorCode;
        $this->messageTemplate = $messageTemplate;
        $this->messageParams   = $messageParams;
    }

    /**
     * @inheritdoc
     */
    public function toBlock(): ExecutionBlockInterface
    {
        $operator = new IfOperator(
            [static::class, 'compare'],
            new Success(),
            new Fail($this->getErrorCode(), $this->getMessageTemplate(), $this->getMessageParameters()),
            [
                static::PROPERTY_LOWER_VALUE => $this->getLowerValue(),
                static::PROPERTY_UPPER_VALUE => $this->getUpperValue(),
            ]
        );

        $operator->setParent($this);
        if ($this->isCaptureEnabled() === true) {
            $operator->enableCapture();
        }

        return $operator->toBlock();
    }

    /**
     * @return mixed
     */
    public function getLowerValue()
    {
        return $this->lowerValue;
    }

    /**
     * @return mixed
     */
    public function getUpperValue()
    {
        return $this->upperValue;
    }

    /**
     * @return int
     */
    protected function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    protected function getMessageTemplate(): string
    {
        return $this->messageTemplate;
    }

    /**
     * @return array
     */
    public function getMessageParameters(): array
    {
        return $this->messageParams;
    }

    /**
     * @param ContextInterface $context
     *
     * @return mixed
     */
    protected static function readLowerValue(ContextInterface $context)
    {
        $limit = $context->getProperties()->getProperty(static::PROPERTY_LOWER_VALUE);

        return $limit;
    }

    /**
     * @param ContextInterface $context
     *
     * @return mixed
     */
    protected static function readUpperValue(ContextInterface $context)
    {
        $limit = $context->getProperties()->getProperty(static::PROPERTY_UPPER_VALUE);

        return $limit;
    }
}
