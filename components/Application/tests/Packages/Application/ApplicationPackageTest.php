<?php declare(strict_types=1);

namespace Limoncello\Tests\Application\Packages\Application;

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

use Limoncello\Application\Packages\Application\ApplicationContainerConfigurator;
use Limoncello\Application\Packages\Application\ApplicationProvider;
use Limoncello\Application\Packages\Application\WhoopsContainerConfigurator;
use Limoncello\Container\Container;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface as S;
use Limoncello\Contracts\Application\CacheSettingsProviderInterface;
use Limoncello\Contracts\Commands\CommandStorageInterface;
use Limoncello\Contracts\Exceptions\ThrowableHandlerInterface;
use Limoncello\Tests\Application\Data\CoreSettings\Providers\Provider1;
use Limoncello\Tests\Application\TestCase;
use Mockery;
use Mockery\Mock;
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
    public function testProvider(): void
    {
        $this->assertNotEmpty(ApplicationProvider::getCommands());
        $this->assertNotEmpty(ApplicationProvider::getContainerConfigurators());
    }

    /**
     * Test container configurator.
     */
    public function testContainerConfigurator(): void
    {
        $container = new Container();

        /** @var Mock $provider */
        $provider = Mockery::mock(CacheSettingsProviderInterface::class);
        $container[CacheSettingsProviderInterface::class] = $provider;
        $container[LoggerInterface::class] = new NullLogger();
        $provider->shouldReceive('getApplicationConfiguration')->once()
            ->withNoArgs()->andReturn($this->getApplicationSettings()->get());

        ApplicationContainerConfigurator::configureContainer($container);
        WhoopsContainerConfigurator::configureContainer($container);

        /** @var CommandStorageInterface $storage */
        $this->assertNotNull($storage = $container->get(CommandStorageInterface::class));
        $this->assertNotEmpty($storage->getAll());
        foreach ($storage->getAll() as $class) {
            $this->assertTrue($storage->has($class));
        }

        $this->assertNotNull($container->get(ThrowableHandlerInterface::class));
    }

    /**
     * @return S
     */
    private function getApplicationSettings(): S
    {
        return new class implements S
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
