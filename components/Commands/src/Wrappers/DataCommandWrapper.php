<?php namespace Limoncello\Commands\Wrappers;

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

use Generator;
use Limoncello\Contracts\Commands\CommandInterface;

/**
 * @package Limoncello\Commands
 */
class DataCommandWrapper
{
    /**
     * @var CommandInterface
     */
    private $command;

    /**
     * @param CommandInterface $command
     */
    public function __construct(CommandInterface $command)
    {
        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->getCommand()->getCommandData()[CommandInterface::COMMAND_NAME];
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->getCommand()->getCommandData()[CommandInterface::COMMAND_DESCRIPTION] ?? null;
    }

    /**
     * @return string|null
     */
    public function getHelp()
    {
        return $this->getCommand()->getCommandData()[CommandInterface::COMMAND_HELP] ?? null;
    }

    /**
     * @return Generator
     */
    public function getArguments(): Generator
    {
        foreach ($this->getCommand()->getArguments() as $argumentData) {
            assert(is_array($argumentData) === true && empty($argumentData) === false);
            yield new DataArgumentWrapper($argumentData);
        }
    }

    /**
     * @return Generator
     */
    public function getOptions(): Generator
    {
        foreach ($this->getCommand()->getOptions() as $optionData) {
            assert(is_array($optionData) === true && empty($optionData) === false);
            yield new DataOptionWrapper($optionData);
        }
    }

    /**
     * @return callable
     */
    public function getExecuteHandler(): callable
    {
        /** @var callable $handler */
        $handler = [get_class($this->getCommand()), CommandInterface::EXECUTE_METHOD];

        assert($this->isValidHandler($handler) === true);

        return $handler;
    }

    /**
     * @return callable|null
     */
    public function getInitializeHandler()
    {
        $handler = $this->getCommand()->getOptionalHandlers()[CommandInterface::HANDLER_INITIALIZE] ?? null;

        assert($handler === null || $this->isValidHandler($handler) === true);

        return $handler;
    }

    /**
     * @return callable|null
     */
    public function getInteractHandler()
    {
        $handler = $this->getCommand()->getOptionalHandlers()[CommandInterface::HANDLER_INTERACT] ?? null;

        assert($handler === null || $this->isValidHandler($handler) === true);

        return $handler;
    }

    /**
     * @return CommandInterface
     */
    protected function getCommand()
    {
        return $this->command;
    }

    /**
     * @param mixed $handler
     *
     * @return bool
     */
    private function isValidHandler($handler): bool
    {
        // TODO add deeper check for `static` and method argument count and types
        return is_callable($handler) === true && (is_array($handler) === true || is_string($handler) === true);
    }
}
