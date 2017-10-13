<?php namespace Limoncello\Application\Packages\Session;

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

use Limoncello\Application\Contracts\Session\SessionFunctionsInterface;
use Limoncello\Application\Session\Session;
use Limoncello\Application\Session\SessionFunctions;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Session\SessionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * @package Limoncello\Application
 */
class SessionContainerConfigurator implements ContainerConfiguratorInterface
{
    /**
     * @inheritdoc
     */
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $container[SessionInterface::class] = function (PsrContainerInterface $container): SessionInterface {
            /** @var SessionFunctionsInterface $functions */
            $functions = $container->get(SessionFunctionsInterface::class);
            $session   = new Session($functions);

            return $session;
        };

        $container[SessionFunctionsInterface::class] = function (): SessionFunctionsInterface {
            $functions = new SessionFunctions();

            return $functions;
        };
    }
}
