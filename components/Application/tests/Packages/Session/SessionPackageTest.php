<?php namespace Limoncello\Tests\Application\Packages\Session;

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
use Limoncello\Application\Packages\Session\SessionContainerConfigurator;
use Limoncello\Application\Packages\Session\SessionProvider;
use Limoncello\Application\Packages\Session\SessionSettings as C;
use Limoncello\Container\Container;
use Limoncello\Contracts\Session\SessionInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Application
 */
class SessionPackageTest extends TestCase
{
    /**
     * Test provider.
     */
    public function testProvider()
    {
        $this->assertNotEmpty(SessionProvider::getSettings());
        $this->assertNotEmpty(SessionProvider::getMiddleware());
        $this->assertNotEmpty(SessionProvider::getContainerConfigurators());
    }

    /**
     * Test container configurator.
     */
    public function testContainerConfigurator()
    {
        $container = new Container();

        /** @var Mock $provider */
        $container[SettingsProviderInterface::class] = $provider = Mockery::mock(SettingsProviderInterface::class);

        $appSettings = [];
        $corsConfig  = (new C())->get($appSettings);
        $provider->shouldReceive('get')->once()->with(C::class)->andReturn($corsConfig);

        SessionContainerConfigurator::configureContainer($container);

        $this->assertNotNull($container->get(SessionInterface::class));
        $this->assertNotNull($container->get(SessionFunctionsInterface::class));
    }
}
