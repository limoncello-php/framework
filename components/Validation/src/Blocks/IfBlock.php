<?php declare(strict_types=1);

namespace Limoncello\Validation\Blocks;

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

use Limoncello\Common\Reflection\CheckCallableTrait;
use Limoncello\Validation\Contracts\Blocks\ExecutionBlockInterface;
use Limoncello\Validation\Contracts\Blocks\IfExpressionInterface;
use Limoncello\Validation\Contracts\Execution\ContextInterface;

/**
 * @package Limoncello\Validation
 */
final class IfBlock implements IfExpressionInterface
{
    use CheckCallableTrait;

    /**
     * @var callable
     */
    private $condition;

    /**
     * @var ExecutionBlockInterface
     */
    private $onTrue;

    /**
     * @var ExecutionBlockInterface
     */
    private $onFalse;

    /**
     * @var array
     */
    private $properties;

    /**
     * @param callable                $condition
     * @param ExecutionBlockInterface $onTrue
     * @param ExecutionBlockInterface $onFalse
     * @param array                   $properties
     */
    public function __construct(
        callable $condition,
        ExecutionBlockInterface $onTrue,
        ExecutionBlockInterface $onFalse,
        array $properties = []
    ) {
        assert($this->checkConditionCallableSignature($condition));

        $this->condition  = $condition;
        $this->onTrue     = $onTrue;
        $this->onFalse    = $onFalse;
        $this->properties = $properties;
    }

    /**
     * @inheritdoc
     */
    public function getConditionCallable(): callable
    {
        return $this->condition;
    }

    /**
     * @inheritdoc
     */
    public function getOnTrue(): ExecutionBlockInterface
    {
        return $this->onTrue;
    }

    /**
     * @inheritdoc
     */
    public function getOnFalse(): ExecutionBlockInterface
    {
        return $this->onFalse;
    }

    /**
     * @inheritdoc
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /** @noinspection PhpDocMissingThrowsInspection
     * @param callable $procedureCallable
     *
     * @return bool
     */
    private function checkConditionCallableSignature(callable $procedureCallable): bool
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return static::checkPublicStaticCallable($procedureCallable, [null, ContextInterface::class], 'bool');
    }
}
