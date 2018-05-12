<?php namespace Limoncello\Application\Packages\Application;

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

use Limoncello\Application\Commands\ApplicationCommand;
use Limoncello\Application\Commands\DataCommand;
use Limoncello\Application\Commands\MakeCommand;
use Limoncello\Contracts\Provider\ProvidesCommandsInterface;
use Limoncello\Contracts\Provider\ProvidesContainerConfiguratorsInterface;

/**
 * @package Limoncello\Application
 */
class ApplicationProvider implements ProvidesContainerConfiguratorsInterface, ProvidesCommandsInterface
{
    /**
     * @inheritdoc
     */
    public static function getContainerConfigurators(): array
    {
        return [
            ApplicationContainerConfigurator::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getCommands(): array
    {
        return [
            ApplicationCommand::class,
            DataCommand::class,
            MakeCommand::class,
        ];
    }
}
