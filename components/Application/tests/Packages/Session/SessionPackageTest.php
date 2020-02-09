<?php declare(strict_types=1);

namespace Limoncello\Tests\Application\Packages\Session;

/**
 * Copyright 2015-2020 info@neomerx.com
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
use Limoncello\Application\Packages\Session\SessionContainerConfigurator;
use Limoncello\Application\Packages\Session\SessionProvider;
use Limoncello\Container\Container;
use Limoncello\Contracts\Session\SessionInterface;
use Limoncello\Tests\Application\TestCase;

/**
 * @package Limoncello\Tests\Application
 */
class SessionPackageTest extends TestCase
{
    /**
     * Test provider.
     */
    public function testProvider(): void
    {
        $this->assertNotEmpty(SessionProvider::getSettings());
        $this->assertNotEmpty(SessionProvider::getMiddleware());
        $this->assertNotEmpty(SessionProvider::getContainerConfigurators());
    }

    /**
     * Test container configurator.
     */
    public function testContainerConfigurator(): void
    {
        $container = new Container();

        SessionContainerConfigurator::configureContainer($container);

        $this->assertNotNull($container->get(SessionInterface::class));
        $this->assertNotNull($container->get(SessionFunctionsInterface::class));
    }
}
