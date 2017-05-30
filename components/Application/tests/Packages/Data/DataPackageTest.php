<?php namespace Limoncello\Tests\Application\Packages\Data;

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

use Doctrine\DBAL\Connection;
use Limoncello\Application\Packages\Data\DataContainerConfigurator;
use Limoncello\Application\Packages\Data\DataProvider;
use Limoncello\Application\Packages\Data\DataSettings;
use Limoncello\Application\Packages\Data\DataSettings as C;
use Limoncello\Application\Packages\Data\DoctrineSettings as S;
use Limoncello\Container\Container;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Tests\Application
 */
class DataPackageTest extends TestCase
{
    /**
     * Test provider.
     */
    public function testProvider()
    {
        $this->assertNotEmpty(DataProvider::getContainerConfigurators());
    }

    /**
     * Test container configurator.
     */
    public function testContainerConfigurator()
    {
        $container = new Container();

        /** @var Mock $provider */
        $container[SettingsProviderInterface::class] = $provider = Mockery::mock(SettingsProviderInterface::class);
        $provider->shouldReceive('get')->once()->with(C::class)->andReturn($this->getDataSettings()->get());
        $provider->shouldReceive('get')->once()->with(S::class)->andReturn([
            S::KEY_URL    => 'sqlite:///',
            S::KEY_MEMORY => true,
        ]);

        DataContainerConfigurator::configureContainer($container);

        $this->assertNotNull($container->get(ModelSchemeInfoInterface::class));
        $this->assertNotNull($container->get(Connection::class));
    }

    /**
     * Test settings.
     */
    public function testSettings()
    {
        $this->assertNotNull($settings = $this->getDataSettings());
        $this->assertNotEmpty($settings->get());
    }

    /**
     * @return DataSettings
     */
    private function getDataSettings(): DataSettings
    {
        return new class extends DataSettings
        {
            /**
             * @inheritdoc
             */
            protected function getModelsPath(): string
            {
                return implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Data', 'Models', '*.php']);
            }

            /**
             * @inheritdoc
             */
            protected function getMigrationsPath(): string
            {
                return '/some/path';
            }

            /**
             * @inheritdoc
             */
            protected function getSeedsPath(): string
            {
                return '/some/path';
            }

            /**
             * @inheritdoc
             */
            protected function getSeedInit()
            {
                return [DataPackageTest::class, 'initSeeder'];
            }
        };
    }

    /**
     * @param ContainerInterface $container
     * @param string             $seedClass
     */
    public static function initSeeder(ContainerInterface $container, string $seedClass)
    {
        assert($container || $seedClass);
    }
}
