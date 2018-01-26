<?php namespace Limoncello\Validation\Rules;

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

use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Execution\BlockReplies;

/**
 * @package Limoncello\Validation
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class BaseRule implements RuleInterface
{
    /**
     * State key.
     */
    const STATE_ERROR_VALUE = 0;

    /**
     * State key.
     */
    const STATE_LAST = self::STATE_ERROR_VALUE;

    /**
     * Property key.
     */
    const PROPERTY_NAME = 0;

    /**
     * Property key.
     */
    const PROPERTY_IS_CAPTURE_ENABLED = self::PROPERTY_NAME + 1;

    /**
     * Property key.
     */
    const PROPERTY_LAST = self::PROPERTY_IS_CAPTURE_ENABLED;

    /**
     * @var string|null
     */
    private $name = null;

    /**
     * @var bool
     */
    private $isCaptureEnabled = false;

    /**
     * @var RuleInterface|null
     */
    private $parent = null;

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        if ($this->name === null) {
            return $this->parent === null ? '' : $this->getParent()->getName();
        }

        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): RuleInterface
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function unsetName(): RuleInterface
    {
        $this->name = null;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function enableCapture(): RuleInterface
    {
        $this->isCaptureEnabled = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function disableCapture(): RuleInterface
    {
        $this->isCaptureEnabled = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isCaptureEnabled(): bool
    {
        return $this->isCaptureEnabled;
    }

    /**
     * @inheritdoc
     */
    public function getParent(): ?RuleInterface
    {
        return $this->parent;
    }

    /**
     * @inheritdoc
     */
    public function setParent(RuleInterface $rule): RuleInterface
    {
        $this->parent = $rule;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function unsetParent(): RuleInterface
    {
        $this->parent = null;

        return $this;
    }

    /**
     * @return array
     */
    protected function getStandardProperties(): array
    {
        return static::composeStandardProperties($this->getName(), $this->isCaptureEnabled());
    }

    /**
     * @param string $name
     * @param bool   $isCaptureEnabled
     *
     * @return array
     */
    protected function composeStandardProperties(string $name, bool $isCaptureEnabled): array
    {
        return [
            static::PROPERTY_NAME               => $name,
            static::PROPERTY_IS_CAPTURE_ENABLED => $isCaptureEnabled,
        ];
    }

    /**
     * @param mixed $result
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected static function createSuccessReply($result): array
    {
        return BlockReplies::createSuccessReply($result);
    }

    /**
     * @param ContextInterface $context
     * @param mixed            $errorValue
     * @param int              $errorCode
     * @param mixed|null       $errorContext
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected static function createErrorReply(
        ContextInterface $context,
        $errorValue,
        int $errorCode,
        $errorContext = null
    ): array {
        return BlockReplies::createErrorReply($context, $errorValue, $errorCode, $errorContext);
    }
}
