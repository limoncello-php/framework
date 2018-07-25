<?php namespace Limoncello\Tests\Application\Packages\Data;

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

use Doctrine\DBAL\Connection;
use Limoncello\Application\Packages\Data\DataContainerConfigurator;
use Limoncello\Application\Packages\Data\DataProvider;
use Limoncello\Application\Packages\Data\DataSettings;
use Limoncello\Application\Packages\Data\DataSettings as C;
use Limoncello\Application\Packages\Data\DoctrineSettings;
use Limoncello\Application\Packages\Data\DoctrineSettings as S;
use Limoncello\Container\Container;
use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Tests\Application\TestCase;
use Mockery;
use Mockery\Mock;
use Psr\Container\ContainerInterface;
use ReflectionException;

/**
 * @package Limoncello\Tests\Application
 */
class DataPackageTest extends TestCase
{
    /**
     * Test provider.
     */
    public function testProvider(): void
    {
        $this->assertNotEmpty(DataProvider::getContainerConfigurators());
    }

    /**
     * Test container configurator.
     *
     * @throws ReflectionException
     */
    public function testContainerConfigurator(): void
    {
        $container = new Container();

        /** @var Mock $provider */
        $container[SettingsProviderInterface::class] = $provider = Mockery::mock(SettingsProviderInterface::class);
        $appSettings = [];
        $provider->shouldReceive('get')->once()->with(C::class)->andReturn($this->getDataSettings()->get($appSettings));
        $provider->shouldReceive('get')->once()->with(S::class)->andReturn([
            S::KEY_URL    => 'sqlite:///',
            S::KEY_MEMORY => true,
            S::KEY_EXEC   => [
                'PRAGMA foreign_keys = ON;'
            ],
        ]);

        DataContainerConfigurator::configureContainer($container);

        $this->assertNotNull($container->get(ModelSchemaInfoInterface::class));
        $this->assertNotNull($container->get(Connection::class));
    }

    /**
     * Test settings.
     *
     * @throws ReflectionException
     */
    public function testSettings(): void
    {
        $appSettings = [];
        $this->assertNotEmpty($this->getDataSettings()->get($appSettings));
        $this->assertNotEmpty($this->getDoctrineSettings()->get($appSettings));
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
            protected function getSettings(): array
            {
                $modelsFolder     = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Data', 'Models']);
                $migrationsFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Data', 'Migrations']);
                $seedsFolder      = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Data', 'Seeds']);

                return [
                        static::KEY_MODELS_FOLDER        => $modelsFolder,
                        static::KEY_MIGRATIONS_FOLDER    => $migrationsFolder,
                        static::KEY_MIGRATIONS_LIST_FILE => $migrationsFolder . DIRECTORY_SEPARATOR . 'migrations.php',
                        static::KEY_SEEDS_FOLDER         => $seedsFolder,
                        static::KEY_SEEDS_LIST_FILE      => $seedsFolder . DIRECTORY_SEPARATOR . 'seeds.php',
                        static::KEY_SEED_INIT            => [DataPackageTest::class, 'initSeeder'],
                    ] + parent::getSettings();
            }
        };
    }

    /**
     * @return DoctrineSettings
     */
    private function getDoctrineSettings(): DoctrineSettings
    {
        return new class extends DoctrineSettings
        {
            /**
             * @inheritdoc
             */
            protected function getSettings(): array
            {
                $dbFile = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Data', 'Seeds', 'dummy.sqlite']);

                return [

                        static::KEY_PATH => $dbFile,

                    ] + parent::getSettings();
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
