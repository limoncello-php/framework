<?php declare(strict_types=1);

namespace Limoncello\Tests\Application\Packages\L10n;

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

use Limoncello\Application\Packages\L10n\L10nContainerConfigurator;
use Limoncello\Application\Packages\L10n\L10nProvider;
use Limoncello\Application\Packages\L10n\L10nSettings as C;
use Limoncello\Container\Container;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface as A;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Tests\Application\Data\CoreSettings\Providers\Provider1;
use Limoncello\Tests\Application\TestCase;
use Mockery;
use Mockery\Mock;

/**
 * @package Limoncello\Tests\Application
 */
class L10nPackageTest extends TestCase
{
    /**
     * Test provider.
     */
    public function testProvider(): void
    {
        $this->assertNotEmpty(L10nProvider::getContainerConfigurators());
    }

    /**
     * Test container configurator.
     */
    public function testContainerConfigurator()
    {
        $container = new Container();

        /** @var Mock $provider */
        $container[SettingsProviderInterface::class] = $provider = Mockery::mock(SettingsProviderInterface::class);
        $provider->shouldReceive('get')->once()->with(C::class)->andReturn($this->getSettings());

        L10nContainerConfigurator::configureContainer($container);

        /** @var FormatterFactoryInterface $factory */
        $this->assertNotNull($factory = $container->get(FormatterFactoryInterface::class));

        $this->assertNotNull($factory->createFormatter('Sample.Messages'));
    }

    /**
     * @return array
     */
    private function getSettings(): array
    {
        $settings = new class extends C
        {
            /**
             * @inheritdoc
             */
            protected function getSettings(): array
            {
                $localesFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Data', 'L10n']);

                return [
                        static::KEY_LOCALES_FOLDER => $localesFolder,
                    ] + parent::getSettings();
            }
        };

        $appSettings = [
            A::KEY_PROVIDER_CLASSES => [
                Provider1::class,
            ],
        ];
        $result      = $settings->get($appSettings);

        return $result;
    }
}
