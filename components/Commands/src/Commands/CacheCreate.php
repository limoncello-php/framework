<?php namespace Limoncello\Commands\Commands;

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

use Limoncello\Application\Commands\ApplicationSettingsCreate;
use Limoncello\Contracts\Commands\CommandInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Templates\Commands\TemplatesCreate;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Commands
 */
class CacheCreate implements CommandInterface
{
    /**
     * @inheritdoc
     */
    public function getCommandData(): array
    {
        return [
            self::COMMAND_NAME        => 'l:cache',
            self::COMMAND_DESCRIPTION => 'Creates application caches.',
            self::COMMAND_HELP        => 'This command creates caches for routes, settings, templates and etc.',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getArguments(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getOptions(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function execute(ContainerInterface $container, IoInterface $inOut)
    {
        (new ApplicationSettingsCreate())->execute($container, $inOut);
        (new TemplatesCreate())->execute($container, $inOut);
    }
}
