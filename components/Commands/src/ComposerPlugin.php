<?php declare(strict_types=1);

namespace Limoncello\Commands;

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

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Limoncello\Commands\Traits\CacheFilePathTrait;
use function assert;
use function array_merge;
use function is_array;

/**
 * @package Limoncello\Commands
 */
class ComposerPlugin implements PluginInterface, Capable
{
    use CacheFilePathTrait;

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function activate(Composer $composer, IOInterface $ioInterface)
    {
        $builtInCommands = [
            new CommandsCommand(),
        ];

        // Due to https://github.com/composer/composer/issues/6315 we cannot load
        // application at this stage.
        //
        // So we create command proxies and when one one of them is called we actually
        // create application and execute the command.
        $commands          = [];
        $commandsCacheFile = $this->getCommandsCacheFilePath($composer);
        if ($commandsCacheFile !== null && file_exists($commandsCacheFile) === true) {
            /** @noinspection PhpIncludeInspection */
            $cacheData = require $commandsCacheFile;
            assert(is_array($cacheData));
            foreach ($cacheData as $commandData) {
                list($name, $description, $help, $arguments, $options, $callable) = $commandData;
                $commands[] = new LimoncelloCommand($name, $description, $help, $arguments, $options, $callable);
            }
        }

        ComposerCommandProvider::setCommands(array_merge($builtInCommands, $commands));
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
}
