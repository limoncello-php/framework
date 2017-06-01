<?php namespace Limoncello\Tests\Application\Packages\Application;

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

use Limoncello\Application\Packages\Application\ApplicationContainerConfigurator;
use Limoncello\Application\Packages\Application\ApplicationProvider;
use Limoncello\Application\Packages\Application\ApplicationSettings as S;
use Limoncello\Container\Container;
use Limoncello\Contracts\Commands\CommandStorageInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Tests\Application\Data\CoreSettings\Providers\Provider1;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @package Limoncello\Tests\Application
 */
class ApplicationPackageTest extends TestCase
{
    /**
     * Test provider.
     */
    public function testProvider()
    {
        $this->assertNotEmpty(ApplicationProvider::getCommands());
        $this->assertNotEmpty(ApplicationProvider::getContainerConfigurators());
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
        $provider->shouldReceive('get')->once()->with(S::class)->andReturn($this->getApplicationSettings()->get());

        ApplicationContainerConfigurator::configureContainer($container);

        /** @var CommandStorageInterface $storage */
        $this->assertNotNull($storage = $container->get(CommandStorageInterface::class));
        $this->assertNotEmpty($storage->getAll());
        foreach ($storage->getAll() as $class) {
            $this->assertTrue($storage->has($class));
        }
    }

    /**
     * @return S
     */
    private function getApplicationSettings(): S
    {
        return new class extends S
        {
            /**
             * @return array
             */
            public function get(): array
            {
                $commandsFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'src', 'Commands']);

                return [
                    S::KEY_PROVIDER_CLASSES => [Provider1::class],
                    S::KEY_COMMANDS_FOLDER  => $commandsFolder,
                ];
            }
        };
    }
}
