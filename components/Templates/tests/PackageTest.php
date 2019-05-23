<?php declare(strict_types=1);

namespace Limoncello\Tests\Templates;

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

use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Contracts\Templates\TemplatesInterface;
use Limoncello\Templates\Contracts\TemplatesCacheInterface;
use Limoncello\Templates\Package\TwigTemplatesContainerConfigurator;
use Limoncello\Templates\Package\TwigTemplatesProvider;
use Limoncello\Templates\Package\TemplatesSettings;
use Limoncello\Tests\Templates\Data\Templates;
use Limoncello\Tests\Templates\Data\TestContainer;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Templates
 */
class PackageTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    /**
     * Test ContainerConfigurator.
     */
    public function testContainerConfigurator()
    {
        $appConfig = [];
        $settings  = (new Templates())->get($appConfig);

        /** @var Mock $settingsMock */
        $settingsMock = Mockery::mock(SettingsProviderInterface::class);
        $settingsMock->shouldReceive('get')->once()->with(TemplatesSettings::class)->andReturn($settings);

        $container = new TestContainer();

        $container[SettingsProviderInterface::class] = $settingsMock;

        TwigTemplatesContainerConfigurator::configureContainer($container);

        $this->assertTrue($container->has(TemplatesInterface::class));
        $this->assertNotNull($container->get(TemplatesInterface::class));

        $this->assertTrue($container->has(TemplatesCacheInterface::class));
        $this->assertNotNull($container->get(TemplatesCacheInterface::class));
    }

    /**
     * Test template provider.
     */
    public function testTemplateProvider()
    {
        $this->assertNotEmpty(TwigTemplatesProvider::getContainerConfigurators());
        $this->assertNotEmpty(TwigTemplatesProvider::getCommands());
    }

    /**
     * Test template settings.
     */
    public function testSettings()
    {
        $appConfig = [];
        $settings  = (new Templates())->get($appConfig);

        $this->assertNotEmpty($settings[Templates::KEY_CACHE_FOLDER]);
        $this->assertNotEmpty($settings[Templates::KEY_TEMPLATES_FOLDER]);
        $this->assertEquals(['Samples/en/test.html.twig'], $settings[Templates::KEY_TEMPLATES_LIST]);
    }
}
