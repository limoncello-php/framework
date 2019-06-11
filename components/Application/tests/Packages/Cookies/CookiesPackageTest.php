<?php declare(strict_types=1);

namespace Limoncello\Tests\Application\Packages\Cookies;

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

use Limoncello\Application\Contracts\Cookie\CookieFunctionsInterface;
use Limoncello\Application\Packages\Cookies\CookieContainerConfigurator;
use Limoncello\Application\Packages\Cookies\CookieProvider;
use Limoncello\Application\Packages\Cookies\CookieSettings as C;
use Limoncello\Container\Container;
use Limoncello\Contracts\Cookies\CookieJarInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Tests\Application\TestCase;
use Mockery;
use Mockery\Mock;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @package Limoncello\Tests\Application
 */
class CookiesPackageTest extends TestCase
{
    /**
     * Test provider.
     */
    public function testProvider(): void
    {
        $this->assertNotEmpty(CookieProvider::getSettings());
        $this->assertNotEmpty(CookieProvider::getMiddleware());
        $this->assertNotEmpty(CookieProvider::getContainerConfigurators());
    }

    /**
     * Test container configurator.
     */
    public function testContainerConfigurator(): void
    {
        $container = new Container();

        /** @var Mock $provider */
        $provider                                    = Mockery::mock(SettingsProviderInterface::class);
        $container[SettingsProviderInterface::class] = $provider;
        $container[LoggerInterface::class]           = new NullLogger();
        $appSettings                                 = [];
        $corsConfig                                  = (new C())->get($appSettings);
        $provider->shouldReceive('get')->once()->with(C::class)->andReturn($corsConfig);

        CookieContainerConfigurator::configureContainer($container);

        $this->assertNotNull($container->get(CookieJarInterface::class));
        $this->assertNotNull($container->get(CookieFunctionsInterface::class));
    }
}
