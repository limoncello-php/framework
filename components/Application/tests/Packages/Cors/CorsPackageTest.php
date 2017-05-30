<?php namespace Limoncello\Tests\Application\Packages\Cors;

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

use Limoncello\Application\Packages\Application\ApplicationSettings as A;
use Limoncello\Application\Packages\Cors\CorsContainerConfigurator;
use Limoncello\Application\Packages\Cors\CorsProvider;
use Limoncello\Application\Packages\Cors\CorsSettings as C;
use Limoncello\Container\Container;
use Limoncello\Contracts\Http\Cors\CorsStorageInterface;
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
    public function testProvider()
    {
        $this->assertNotEmpty(CorsProvider::getSettings());
        $this->assertNotEmpty(CorsProvider::getMiddleware());
        $this->assertNotEmpty(CorsProvider::getContainerConfigurators());
    }

    /**
     * Test container configurator.
     */
    public function testContainerConfigurator()
    {
        $container = new Container();

        /** @var Mock $provider */
        $container[SettingsProviderInterface::class] = $provider = Mockery::mock(SettingsProviderInterface::class);
        $container[LoggerInterface::class] = new NullLogger();
        $provider->shouldReceive('get')->once()->with(A::class)->andReturn([
            A::KEY_IS_DEBUG => true,
        ]);
        $provider->shouldReceive('get')->once()->with(C::class)->andReturn((new C())->get());

        CorsContainerConfigurator::configureContainer($container);

        $this->assertNotNull($container->get(AnalyzerInterface::class));
        $this->assertNotNull($container->get(CorsStorageInterface::class));
    }
}
