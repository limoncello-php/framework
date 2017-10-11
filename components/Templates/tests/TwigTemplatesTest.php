<?php namespace Limoncello\Tests\Templates;

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

use Limoncello\Templates\TwigTemplates;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use Twig_Environment;

/**
 * @package Limoncello\Tests\Templates
 */
class TwigTemplatesTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }

    /**
     * Test constructor.
     */
    public function testConstructor()
    {
        // '[]' means no methods will be mocked
        $templates = Mockery::mock(TwigTemplates::class . '[]', [
            __DIR__,
            __DIR__,
            __DIR__,
            false,
        ]);

        $this->assertNotNull($templates);
    }

    /**
     * Test getter.
     */
    public function testGetTwig()
    {
        // '[]' means no methods will be mocked
        /** @var TwigTemplates $templates */
        $templates = Mockery::mock(TwigTemplates::class . '[]', [
            __DIR__,
            __DIR__,
            __DIR__,
            false,
        ]);

        $this->assertNotNull($templates->getTwig());
    }

    /**
     * Test render.
     */
    public function testRender()
    {
        /** @var Mock $templates */
        $templates = Mockery::mock(TwigTemplates::class . '[getTwig]', [
            __DIR__,
            __DIR__,
            __DIR__,
            false,
        ]);

        /** @var Mock $twig */
        $twig = Mockery::mock(Twig_Environment::class);

        $templateName = 'some_template_name';
        $templateContext = [];
        $templates->shouldReceive('getTwig')->once()->withNoArgs()->andReturn($twig);
        $twig->shouldReceive('render')->once()->with($templateName, $templateContext)->andReturnSelf();

        /** @var TwigTemplates $templates */

        $this->assertNotNull($templates->render($templateName, $templateContext));
    }

    /**
     * Test render.
     */
    public function testCache()
    {
        /** @var Mock $templates */
        $templates = Mockery::mock(TwigTemplates::class . '[getTwig]', [
            __DIR__,
            __DIR__,
            __DIR__,
            false,
        ]);

        /** @var Mock $twig */
        $twig = Mockery::mock(Twig_Environment::class);

        $templateName = 'some_template_name';
        $templates->shouldReceive('getTwig')->once()->withNoArgs()->andReturn($twig);
        $twig->shouldReceive('resolveTemplate')->once()->with($templateName)->andReturnSelf();

        /** @var TwigTemplates $templates */

        $templates->cache($templateName);

        // mocks does the checks
        $this->assertTrue(true);
    }
}
