<?php namespace Limoncello\Tests\Application\Packages\Cors;

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

use Limoncello\Application\Packages\Cors\CorsContainerConfigurator;
use Limoncello\Application\Packages\Cors\CorsProvider;
use Limoncello\Application\Packages\Cors\CorsSettings as C;
use Limoncello\Container\Container;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface as A;
use Limoncello\Contracts\Application\CacheSettingsProviderInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Mockery;
use Mockery\Mock;
use Neomerx\Cors\Contracts\AnalyzerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @package Limoncello\Tests\Application
 */
class CorsPackageTest extends TestCase
{
    /**
     * Test provider.
     */
    public function testProvider(): void
    {
        $this->assertNotEmpty(CorsProvider::getSettings());
        $this->assertNotEmpty(CorsProvider::getMiddleware());
        $this->assertNotEmpty(CorsProvider::getContainerConfigurators());
    }

    /**
     * Test container configurator.
     */
    public function testContainerConfigurator(): void
    {
        $container = new Container();

        $appConfig = [
            A::KEY_IS_DEBUG          => true,
            A::KEY_IS_LOG_ENABLED    => true,
            A::KEY_APP_ORIGIN_SCHEMA => 'http',
            A::KEY_APP_ORIGIN_HOST   => 'localhost',
            A::KEY_APP_ORIGIN_PORT   => '8080',
        ];

        /** @var Mock $provider */
        $provider                                         = Mockery::mock(CacheSettingsProviderInterface::class);
        $container[SettingsProviderInterface::class]      = $provider;
        $container[CacheSettingsProviderInterface::class] = $provider;
        $container[LoggerInterface::class]                = new NullLogger();
        $provider->shouldReceive('getApplicationConfiguration')->once()->withNoArgs()->andReturn($appConfig);

        $corsConfig = (new C())->get($appConfig);
        // check CORS config uses application configuration
        $this->assertEquals([
            C::KEY_SERVER_ORIGIN_SCHEMA => 'http',
            C::KEY_SERVER_ORIGIN_HOST   => 'localhost',
            C::KEY_SERVER_ORIGIN_PORT   => '8080',
        ], $corsConfig[C::KEY_SERVER_ORIGIN]);
        $provider->shouldReceive('get')->once()->with(C::class)->andReturn($corsConfig);

        CorsContainerConfigurator::configureContainer($container);

        $this->assertNotNull($container->get(AnalyzerInterface::class));
    }
}
