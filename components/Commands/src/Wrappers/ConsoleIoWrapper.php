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

use Limoncello\Contracts\Commands\IoInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Limoncello\Commands
 */
class ConsoleIoWrapper implements IoInterface
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
    }

    /**
     * @inheritdoc
     */
    public function hasArgument(string $name): bool
    {
        return $this->getInput()->hasArgument($name);
    }

    /**
     * @inheritdoc
     */
    public function getArgument(string $name)
    {
        return $this->getInput()->getArgument($name);
    }

    /**
     * @inheritdoc
     */
    public function getArguments(): array
    {
        return $this->getInput()->getArguments();
    }

    /**
     * @inheritdoc
     */
    public function hasOption(string $name): bool
    {
        return $this->getInput()->hasOption($name);
    }

    /**
     * @inheritdoc
     */
    public function getOption(string $name)
    {
        return $this->getInput()->getOption($name);
    }

    /**
     * @inheritdoc
     */
    public function getOptions(): array
    {
        return $this->getInput()->getOptions();
    }

    /**
     * @inheritdoc
     */
    public function writeInfo(string $message): IoInterface
    {
        $this->getOutput()->write("<info>$message</info>>");
    }

    /**
     * @inheritdoc
     */
    public function writeWarning(string $message): IoInterface
    {
        $this->getOutput()->write("<comment>$message</comment>>");
    }

    /**
     * @inheritdoc
     */
    public function writeError(string $message): IoInterface
    {
        $this->getOutput()->write("<error>$message</error>>");
    }

    /**
     * @return OutputInterface
     */
    protected function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * @return InputInterface
     */
    protected function getInput(): InputInterface
    {
        return $this->input;
    }
}
