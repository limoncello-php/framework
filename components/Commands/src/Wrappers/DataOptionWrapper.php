<?php namespace Limoncello\Commands\Wrappers;

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

use Limoncello\Contracts\Commands\CommandInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @package Limoncello\Commands
 */
class DataOptionWrapper
{
    /**
     * @var array
     */
    private $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;

        // only one true or all false
        assert(
            ($this->isNone() xor $this->isRequired() xor $this->isOptional()) ||
            (!$this->isNone() && !$this->isRequired() && !$this->isOptional())
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->getData()[CommandInterface::OPTION_NAME];
    }

    /**
     * @return string|null
     */
    public function getShortcut(): ?string
    {
        return $this->getData()[CommandInterface::OPTION_SHORTCUT] ?? null;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->getData()[CommandInterface::OPTION_DESCRIPTION] ?? '';
    }

    /**
     * @return string|null
     */
    public function getDefault(): ?string
    {
        $value = $this->getData()[CommandInterface::OPTION_DEFAULT] ?? null;

        assert(is_string($value) === true || $value === null);

        return $value;
    }

    /**
     * @return bool
     */
    public function isNone(): bool
    {
        return $this->getModeValue() & CommandInterface::OPTION_MODE__NONE > 0;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->getModeValue() & CommandInterface::OPTION_MODE__REQUIRED > 0;
    }

    /**
     * @return bool
     */
    public function isOptional(): bool
    {
        return $this->getModeValue() & CommandInterface::OPTION_MODE__OPTIONAL > 0;
    }

    /**
     * @return bool
     */
    public function isArray(): bool
    {
        return $this->getModeValue() & CommandInterface::OPTION_MODE__IS_ARRAY > 0;
    }

    /**
     * @return int|null
     */
    public function getMode(): ?int
    {
        $mode = null;

        $this->isNone() === false     ?: $mode = (int)$mode | InputOption::VALUE_NONE;
        $this->isRequired() === false ?: $mode = (int)$mode | InputOption::VALUE_REQUIRED;
        $this->isOptional() === false ?: $mode = (int)$mode | InputOption::VALUE_OPTIONAL;
        $this->isArray() === false    ?: $mode = (int)$mode | InputOption::VALUE_IS_ARRAY;

        return $mode;
    }

    /**
     * @return int
     */
    protected function getModeValue(): int
    {
        return $this->getData()[CommandInterface::OPTION_MODE] ?? 0;
    }

    /**
     * @return array
     */
    protected function getData(): array
    {
        return $this->data;
    }
}
