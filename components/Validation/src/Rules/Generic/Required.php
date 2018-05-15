<?php namespace Limoncello\Validation\Rules\Generic;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use Limoncello\Validation\Blocks\AndBlock;
use Limoncello\Validation\Blocks\ProcedureBlock;
use Limoncello\Validation\Contracts\Blocks\ExecutionBlockInterface;
use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Execution\BlockReplies;
use Limoncello\Validation\Rules\BaseRule;

/**
 * @package Limoncello\Validation
 */
final class Required extends BaseRule
{
    /**
     * State key.
     */
    const STATE_HAS_BEEN_CALLED = self::STATE_LAST + 1;

    /**
     * @var RuleInterface
     */
    private $rule;

    /**
     * @param RuleInterface $rule
     */
    public function __construct(RuleInterface $rule)
    {
        $this->rule = $rule;
    }

    /**
     * @inheritdoc
     */
    public function toBlock(): ExecutionBlockInterface
    {
        $calledCheck = (new ProcedureBlock([self::class, 'execute']))
            ->setProperties($this->composeStandardProperties($this->getName(), false))
            ->setEndCallable([self::class, 'end']);
        $required    = new AndBlock(
            $calledCheck,
            $this->getRule()->setParent($this)->toBlock(),
            $this->getStandardProperties()
        );

        return $required;
    }

    /**
     * @param mixed            $value
     * @param ContextInterface $context
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function execute($value, ContextInterface $context): array
    {
        $context->getStates()->setState(static::STATE_HAS_BEEN_CALLED, true);

        return static::createSuccessReply($value);
    }

    /**
     * @param ContextInterface $context
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function end(ContextInterface $context): array
    {
        $isOk = $context->getStates()->getState(static::STATE_HAS_BEEN_CALLED, false);

        return $isOk === true ?
            BlockReplies::createEndSuccessReply() : BlockReplies::createEndErrorReply($context, ErrorCodes::REQUIRED);
    }

    /**
     * @return RuleInterface
     */
    public function getRule(): RuleInterface
    {
        return $this->rule;
    }
}
