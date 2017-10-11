<?php namespace Limoncello\Tests\Application\Packages\Monolog;

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

use Limoncello\Application\Packages\Monolog\MonologFileContainerConfigurator;
use Limoncello\Application\Packages\Monolog\MonologFileProvider;
use Limoncello\Application\Packages\Monolog\MonologFileSettings as C;
use Limoncello\Container\Container;
use Limoncello\Contracts\Application\ApplicationSettingsInterface as A;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @package Limoncello\Tests\Application
 */
class MonologPackageTest extends TestCase
{
    /**
     * Test provider.
     */
    public function testProvider(): void
    {
        $this->assertNotEmpty(MonologFileProvider::getContainerConfigurators());
    }

    /**
     * Test container configurator.
     */
    public function testContainerConfigurator(): void
    {
        $container = new Container();

        /** @var Mock $provider */
        $container[SettingsProviderInterface::class] = $provider = Mockery::mock(SettingsProviderInterface::class);
        $provider->shouldReceive('get')->once()->with(A::class)->andReturn([
            A::KEY_APP_NAME => 'Test_App',
        ]);
        $provider->shouldReceive('get')->once()->with(C::class)->andReturn([
            C::KEY_IS_ENABLED => true,
            C::KEY_LOG_PATH   => '/some/path',
        ]);

        MonologFileContainerConfigurator::configureContainer($container);

        $this->assertNotNull($container->get(LoggerInterface::class));
    }

    /**
     * Test settings.
     */
    public function testSettings(): void
    {
        $this->assertNotEmpty($this->getSettings()->get());
    }

    /**
     * @return C
     */
    private function getSettings(): C
    {
        return new class extends C
        {
            /**
             * @inheritdoc
             */
            protected function getSettings(): array
            {
                return [

                    C::KEY_LOG_FOLDER => __DIR__,

                    ] + parent::getSettings();
            }
        };
    }
}
