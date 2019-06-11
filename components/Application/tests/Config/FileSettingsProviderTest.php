<?php declare(strict_types=1);

namespace Limoncello\Tests\Application\Config;

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

use Limoncello\Application\Settings\FileSettingsProvider;
use Limoncello\Contracts\Settings\SettingsInterface;
use Limoncello\Tests\Application\Data\Config\MarkerInterfaceChild1;
use Limoncello\Tests\Application\Data\Config\MarkerInterfaceChild11;
use Limoncello\Tests\Application\Data\Config\MarkerInterfaceChild11And21;
use Limoncello\Tests\Application\Data\Config\MarkerInterfaceChild2;
use Limoncello\Tests\Application\Data\Config\MarkerInterfaceChild21;
use Limoncello\Tests\Application\Data\Config\MarkerInterfaceStandalone;
use Limoncello\Tests\Application\Data\Config\MarkerInterfaceTop;
use Limoncello\Tests\Application\Data\Config\NoDefaultConstructorClass;
use Limoncello\Tests\Application\Data\Config\PrivateConstructorClass;
use Limoncello\Tests\Application\Data\Config\SampleSettingsA;
use Limoncello\Tests\Application\Data\Config\SampleSettingsAA;
use Limoncello\Tests\Application\Data\Config\SampleSettingsB;
use Limoncello\Tests\Application\Data\Config\SampleSettingsBB;
use Limoncello\Tests\Application\TestCase;
use ReflectionException;
use ReflectionMethod;

/**
 * @package Limoncello\Tests\Application
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FileSettingsProviderTest extends TestCase
{
    /**
     * Test loading from folder.
     *
     * @throws ReflectionException
     */
    public function testLoadFromFolder(): void
    {
        $provider = $this->createProvider();

        $appSettings = [];
        $valuesA     = (new SampleSettingsAA())->get($appSettings);
        $valuesB     = (new SampleSettingsBB())->get($appSettings);

        $this->assertFalse($provider->has(MarkerInterfaceTop::class));
        $this->assertTrue($provider->isAmbiguous(MarkerInterfaceTop::class));

        $this->assertTrue($provider->has(MarkerInterfaceChild1::class));
        $this->assertFalse($provider->isAmbiguous(MarkerInterfaceChild1::class));
        $this->assertEquals($valuesA, $provider->get(MarkerInterfaceChild1::class));

        $this->assertTrue($provider->has(MarkerInterfaceChild2::class));
        $this->assertFalse($provider->isAmbiguous(MarkerInterfaceChild2::class));
        $this->assertEquals($valuesB, $provider->get(MarkerInterfaceChild2::class));

        $this->assertTrue($provider->has(MarkerInterfaceChild11::class));
        $this->assertFalse($provider->isAmbiguous(MarkerInterfaceChild11::class));
        $this->assertEquals($valuesA, $provider->get(MarkerInterfaceChild11::class));

        $this->assertFalse($provider->has(MarkerInterfaceChild11And21::class));
        $this->assertTrue($provider->isAmbiguous(MarkerInterfaceChild11And21::class));

        $this->assertTrue($provider->has(MarkerInterfaceChild21::class));
        $this->assertFalse($provider->isAmbiguous(MarkerInterfaceChild21::class));
        $this->assertEquals($valuesB, $provider->get(MarkerInterfaceChild21::class));

        $this->assertTrue($provider->has(MarkerInterfaceStandalone::class));
        $this->assertFalse($provider->isAmbiguous(MarkerInterfaceStandalone::class));
        $this->assertEquals($valuesB, $provider->get(MarkerInterfaceStandalone::class));

        $this->assertTrue($provider->has(SampleSettingsA::class));
        $this->assertFalse($provider->isAmbiguous(SampleSettingsA::class));
        $this->assertEquals($valuesA, $provider->get(SampleSettingsA::class));

        $this->assertTrue($provider->has(SampleSettingsAA::class));
        $this->assertFalse($provider->isAmbiguous(SampleSettingsAA::class));
        $this->assertEquals($valuesA, $provider->get(SampleSettingsAA::class));

        $this->assertTrue($provider->has(SampleSettingsB::class));
        $this->assertFalse($provider->isAmbiguous(SampleSettingsB::class));
        $this->assertEquals($valuesB, $provider->get(SampleSettingsB::class));

        $this->assertTrue($provider->has(SampleSettingsBB::class));
        $this->assertFalse($provider->isAmbiguous(SampleSettingsBB::class));
        $this->assertEquals($valuesB, $provider->get(SampleSettingsBB::class));

        $this->assertEquals([
            MarkerInterfaceTop::class          => true,
            MarkerInterfaceChild11And21::class => true,
            SettingsInterface::class           => true,
        ], $provider->getAmbiguousMap());

        $this->assertEquals([
            0 => $valuesA,
            1 => $valuesB,
        ], $provider->getSettingsData());

        $this->assertEmpty(array_diff_assoc($provider->getSettingsMap(), [
            SampleSettingsA::class           => 0,
            SampleSettingsAA::class          => 0,
            MarkerInterfaceChild1::class     => 0,
            MarkerInterfaceChild11::class    => 0,
            SampleSettingsB::class           => 1,
            SampleSettingsBB::class          => 1,
            MarkerInterfaceChild2::class     => 1,
            MarkerInterfaceChild21::class    => 1,
            MarkerInterfaceStandalone::class => 1,
        ]));
    }

    /**
     * @expectedException \Limoncello\Application\Exceptions\NotRegisteredSettingsException
     *
     * @throws ReflectionException
     */
    public function testGetNotRegistered(): void
    {
        $this->createProvider()->get(static::class);
    }

    /**
     * @expectedException \Limoncello\Application\Exceptions\AmbiguousSettingsException
     *
     * @throws ReflectionException
     */
    public function testGetAmbiguous(): void
    {
        $this->createProvider()->get(MarkerInterfaceTop::class);
    }

    /**
     * @expectedException \Limoncello\Application\Exceptions\AlreadyRegisteredSettingsException
     *
     * @throws ReflectionException
     */
    public function testRegisterTwice(): void
    {
        $this->createProvider()->register(new SampleSettingsA());
    }

    /**
     * @expectedException \Limoncello\Application\Exceptions\InvalidSettingsClassException
     *
     * @throws ReflectionException
     */
    public function testCheckClassWithPrivateConstructor(): void
    {
        $this->invokeCheckMethod(PrivateConstructorClass::class);
    }

    /**
     * @expectedException \Limoncello\Application\Exceptions\InvalidSettingsClassException
     *
     * @throws ReflectionException
     */
    public function testCheckClassWithNonDefaultConstructor(): void
    {
        $this->invokeCheckMethod(NoDefaultConstructorClass::class);
    }

    /**
     * @expectedException \Limoncello\Application\Exceptions\InvalidSettingsClassException
     *
     * @throws ReflectionException
     */
    public function testCheckNotAClass(): void
    {
        $this->invokeCheckMethod(__FILE__);
    }

    /**
     * @param string $className
     *
     * @return bool
     *
     * @throws ReflectionException
     */
    private function invokeCheckMethod(string $className): bool
    {
        $reflectionMethod =
            new ReflectionMethod(FileSettingsProvider::class, 'checkDoNotHaveRequiredParametersOnCreate');
        $reflectionMethod->setAccessible(true);

        $appSettings = [];

        return $reflectionMethod->invoke(new FileSettingsProvider($appSettings), $className);
    }

    /**
     * @return FileSettingsProvider
     *
     * @throws ReflectionException
     */
    private function createProvider(): FileSettingsProvider
    {
        $appSettings = [];

        return (new FileSettingsProvider($appSettings))->load(
            implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Data', 'Config', '*.php'])
        );
    }
}
