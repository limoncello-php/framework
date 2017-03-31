<?php namespace Limoncello\Commands;

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

use Composer\Command\BaseCommand;
use Limoncello\Commands\Wrappers\ConsoleIoWrapper;
use Limoncello\Commands\Wrappers\DataArgumentWrapper;
use Limoncello\Commands\Wrappers\DataCommandWrapper;
use Limoncello\Commands\Wrappers\DataOptionWrapper;
use Limoncello\Contracts\Commands\CommandInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Limoncello\Commands
 */
class LimoncelloCommand extends BaseCommand
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var DataCommandWrapper
     */
    private $wrapper;

    /**
     * @param CommandInterface $command
     */
    public function __construct(CommandInterface $command)
    {
        $this->wrapper = new DataCommandWrapper($command);

        parent::__construct($this->getWrapper()->getName());
    }

    /**
     * @inheritdoc
     */
    public function configure()
    {
        parent::configure();

        $this
            ->setDescription($this->getWrapper()->getDescription())
            ->setHelp($this->getWrapper()->getHelp());

        foreach ($this->getWrapper()->getArguments() as $arg) {
            /** @var DataArgumentWrapper $arg */
            $this->addArgument($arg->getName(), $arg->getMode(), $arg->getDescription(), $arg->getDefault());
        }

        foreach ($this->getWrapper()->getOptions() as $opt) {
            /** @var DataOptionWrapper $opt */
            $this->addOption(
                $opt->getName(),
                $opt->getShortcut(),
                $opt->getMode(),
                $opt->getDescription(),
                $opt->getDefault()
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $handler = $this->getWrapper()->getInitializeHandler();
        if ($handler !== null) {
            call_user_func(
                $handler,
                $this->getContainer(),
                $this->wrapIo($input, $output)
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        $handler = $this->getWrapper()->getInteractHandler();
        if ($handler !== null) {
            call_user_func(
                $handler,
                $this->getContainer(),
                $this->wrapIo($input, $output)
            );
        }
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        call_user_func(
            $this->getWrapper()->getExecuteHandler(),
            $this->getContainer(),
            $this->wrapIo($input, $output)
        );
    }

    /**
     * @return ContainerInterface
     */
    private function getContainer()
    {
        return $this->container;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return IoInterface
     */
    private function wrapIo(InputInterface $input, OutputInterface $output): IoInterface
    {
        return new ConsoleIoWrapper($input, $output);
    }

    /**
     * @return DataCommandWrapper
     */
    private function getWrapper(): DataCommandWrapper
    {
        return $this->wrapper;
    }
}
