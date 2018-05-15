<?php namespace Limoncello\Tests\Events;

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

use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Events\Contracts\EventEmitterInterface;
use Limoncello\Events\Package\EventProvider;
use Limoncello\Events\Package\EventSettings as BaseEventSettings;
use Limoncello\Tests\Events\Data\EventSettings;
use Limoncello\Tests\Events\Data\TestContainer;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * @package Limoncello\Tests\Events
 */
class ContainerConfiguratorTest extends TestCase
{
    /**
     * Test provider.
     *
     * @throws ReflectionException
     */
    public function testEventProvider()
    {
        /** @var ContainerConfiguratorInterface $configuratorClass */
        list($configuratorClass) = EventProvider::getContainerConfigurators();
        $container = new TestContainer();

        $appConfig = [];
        $this->addSettings($container, BaseEventSettings::class, (new EventSettings())->get($appConfig));

        $configuratorClass::configureContainer($container);

        $this->assertNotNull($container->get(EventEmitterInterface::class));
    }

    /**
     * @param ContainerInterface $container
     * @param string             $settingsClass
     * @param array              $settings
     *
     * @return self
     */
    private function addSettings(ContainerInterface $container, string $settingsClass, array $settings): self
    {
        /** @var Mock $settingsMock */
        $settingsMock = Mockery::mock(SettingsProviderInterface::class);
        $settingsMock->shouldReceive('get')->once()->with($settingsClass)->andReturn($settings);

        $container->offsetSet(SettingsProviderInterface::class, $settingsMock);

        return $this;
    }
}
