<?php namespace Limoncello\Contracts\Commands;

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

use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Contracts
 */
interface CommandInterface
{
    /** Data command key */
    const COMMAND_NAME = 0;

    /** Data command key */
    const COMMAND_DESCRIPTION = self::COMMAND_NAME + 1;

    /** Data command key */
    const COMMAND_HELP = self::COMMAND_DESCRIPTION + 1;

    /** Data argument key */
    const ARGUMENT_NAME = 0;

    /** Data argument key */
    const ARGUMENT_DESCRIPTION = self::ARGUMENT_NAME + 1;

    /** Data argument key */
    const ARGUMENT_DEFAULT = self::ARGUMENT_DESCRIPTION + 1;

    /** Data argument key */
    const ARGUMENT_MODE = self::ARGUMENT_DEFAULT + 1;

    /** Data argument key */
    const ARGUMENT_MODE__REQUIRED = 1;

    /** Data argument key */
    const ARGUMENT_MODE__OPTIONAL = 2;

    /** Data argument key */
    const ARGUMENT_MODE__IS_ARRAY = 4;

    /** Data option key */
    const OPTION_NAME = 0;

    /** Data option key */
    const OPTION_SHORTCUT = self::OPTION_NAME + 1;

    /** Data option key */
    const OPTION_DESCRIPTION = self::OPTION_SHORTCUT + 1;

    /** Data option key */
    const OPTION_DEFAULT = self::OPTION_DESCRIPTION + 1;

    /** Data option key */
    const OPTION_MODE = self::OPTION_DEFAULT + 1;

    /** Data option key */
    const OPTION_MODE__NONE = 1;

    /** Data option key */
    const OPTION_MODE__REQUIRED = 2;

    /** Data option key */
    const OPTION_MODE__OPTIONAL = 4;

    /** Data option key */
    const OPTION_MODE__IS_ARRAY = 8;

    /**
     * @return string[]
     */
    public function getCommandData(): array;

    /**
     * @return array
     */
    public function getArguments(): array;

    /**
     * @return array
     */
    public function getOptions(): array;

    /**
     * @param ContainerInterface $container
     * @param IoInterface        $inOut
     * @param ContextInterface   $context
     *
     * @return void
     */
    public function execute(ContainerInterface $container, IoInterface $inOut, ContextInterface $context);
}
