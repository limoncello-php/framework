<?php declare(strict_types=1);

namespace Limoncello\Validation\Blocks;

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

use Limoncello\Common\Reflection\CheckCallableTrait;
use Limoncello\Validation\Contracts\Blocks\ProcedureBlockInterface;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use function assert;

/**
 * @package Limoncello\Validation
 */
final class ProcedureBlock implements ProcedureBlockInterface
{
    use CheckCallableTrait;

    /**
     * @var callable|null
     */
    private $startCallable;

    /**
     * @var callable|null
     */
    private $endCallable;

    /**
     * @var callable
     */
    private $executeCallable;

    /**
     * @var array
     */
    private $properties;

    /**
     * @param callable      $executeCallable
     * @param array         $properties
     * @param callable|null $startCallable
     * @param callable|null $endCallable
     */
    public function __construct(
        callable $executeCallable,
        array $properties = [],
        callable $startCallable = null,
        callable $endCallable = null
    ) {
        $this->setExecuteCallable($executeCallable)->setProperties($properties);

        if ($startCallable !== null) {
            $this->setStartCallable($startCallable);
        }

        if ($endCallable !== null) {
            $this->setEndCallable($endCallable);
        }
    }

    /**
     * @inheritdoc
     */
    public function getStartCallable(): ?callable
    {
        return $this->startCallable;
    }

    /**
     * @inheritdoc
     */
    public function getExecuteCallable(): callable
    {
        return $this->executeCallable;
    }

    /**
     * @inheritdoc
     */
    public function getEndCallable(): ?callable
    {
        return $this->endCallable;
    }

    /**
     * @inheritdoc
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     *
     * @return self
     */
    public function setProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @param callable $startCallable
     *
     * @return self
     */
    public function setStartCallable(callable $startCallable): self
    {
        assert($this->checkProcedureStartOrEndCallableSignature($startCallable));

        $this->startCallable = $startCallable;

        return $this;
    }

    /**
     * @param callable $endCallable
     *
     * @return self
     */
    public function setEndCallable(callable $endCallable): self
    {
        assert($this->checkProcedureStartOrEndCallableSignature($endCallable));

        $this->endCallable = $endCallable;

        return $this;
    }

    /**
     * @param callable $executeCallable
     *
     * @return self
     */
    private function setExecuteCallable(callable $executeCallable): self
    {
        assert($this->checkProcedureExecuteCallableSignature($executeCallable));

        $this->executeCallable = $executeCallable;

        return $this;
    }

    /** @noinspection PhpDocMissingThrowsInspection
     * @param callable $procedureCallable
     *
     * @return bool
     */
    private function checkProcedureExecuteCallableSignature(callable $procedureCallable): bool
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return static::checkPublicStaticCallable($procedureCallable, [null, ContextInterface::class, null], 'array');
    }

    /** @noinspection PhpDocMissingThrowsInspection
     * @param callable $procedureCallable
     *
     * @return bool
     */
    private function checkProcedureStartOrEndCallableSignature(callable $procedureCallable): bool
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return static::checkPublicStaticCallable($procedureCallable, [ContextInterface::class], 'array');
    }
}
