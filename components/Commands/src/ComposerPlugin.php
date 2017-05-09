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

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Limoncello\Application\Commands\DataCommand;
use Limoncello\Commands\Commands\CacheClean;
use Limoncello\Commands\Commands\CacheCreate;

/**
 * @package Limoncello\Commands
 */
class ComposerPlugin implements PluginInterface, Capable
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $ioInterface;

    /**
     * @inheritdoc
     */
    public function activate(Composer $composer, IOInterface $ioInterface)
    {
        $this->setComposer($composer)->setIoInterface($ioInterface)->loadCommands();
    }

    /**
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function loadCommands()
    {
        // Due to https://github.com/composer/composer/issues/6315 we cannot load command from
        // application settings (application itself and providers).
        // So ATM fixed list of command is possible.
        $commands = [
            new LimoncelloCommand(new CacheCreate()),
            new LimoncelloCommand(new CacheClean()),
            new LimoncelloCommand(new DataCommand()),
        ];

        ComposerCommandProvider::setCommands($commands);
    }

    /**
     * @inheritdoc
     */
    public function getCapabilities()
    {
        return [
            CommandProvider::class => ComposerCommandProvider::class,
        ];
    }

    /**
     * @return Composer
     */
    protected function getComposer(): Composer
    {
        return $this->composer;
    }

    /**
     * @param Composer $composer
     *
     * @return ComposerPlugin
     */
    protected function setComposer(Composer $composer): ComposerPlugin
    {
        $this->composer = $composer;

        return $this;
    }

    /**
     * @return IOInterface
     */
    protected function getIoInterface(): IOInterface
    {
        return $this->ioInterface;
    }

    /**
     * @param IOInterface $ioInterface
     *
     * @return ComposerPlugin
     */
    protected function setIoInterface(IOInterface $ioInterface): ComposerPlugin
    {
        $this->ioInterface = $ioInterface;

        return $this;
    }
}
