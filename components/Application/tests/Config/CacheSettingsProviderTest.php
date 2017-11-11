<?php namespace Limoncello\Tests\Application\Config;

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

use Limoncello\Application\Settings\CacheSettingsProvider;
use Limoncello\Application\Settings\FileSettingsProvider;
use Limoncello\Tests\Application\CoreSettings\CoreDataTest;
use Limoncello\Tests\Application\Data\Config\MarkerInterfaceChild1;
use Limoncello\Tests\Application\Data\Config\MarkerInterfaceTop;
use Limoncello\Tests\Application\Data\Config\SampleSettingsAA;
use Limoncello\Tests\Application\TestCase;

/**
 * @package Limoncello\Tests\Application
 */
class CacheSettingsProviderTest extends TestCase
{
    /**
     * Test loading from folder.
     */
    public function testLoadFromFolder()
    {
        $provider = $this->createProvider();
        $provider->unserialize($provider->serialize());

        $appSettings = [];
        $valuesA     = (new SampleSettingsAA())->get($appSettings);

        $this->assertFalse($provider->has(MarkerInterfaceTop::class));
        $this->assertTrue($provider->isAmbiguous(MarkerInterfaceTop::class));

        $this->assertTrue($provider->has(MarkerInterfaceChild1::class));
        $this->assertFalse($provider->isAmbiguous(MarkerInterfaceChild1::class));
        $this->assertEquals($valuesA, $provider->get(MarkerInterfaceChild1::class));
    }

    /**
     * @return CacheSettingsProvider
     */
    private function createProvider(): CacheSettingsProvider
    {
        $appSettings          = [];
        $fileSettingsProvider = (new FileSettingsProvider($appSettings))->load(
            __DIR__ . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'Config'
            . DIRECTORY_SEPARATOR . '*.php'
        );

        $coreData = CoreDataTest::createCoreData();

        return (new CacheSettingsProvider())->setInstanceSettings($coreData, $fileSettingsProvider);
    }

    /**
     * @expectedException \Limoncello\Application\Exceptions\NotRegisteredSettingsException
     */
    public function testGetNotRegistered()
    {
        $this->createProvider()->get(static::class);
    }

    /**
     * @expectedException \Limoncello\Application\Exceptions\AmbiguousSettingsException
     */
    public function testGetAmbiguous()
    {
        $this->createProvider()->get(MarkerInterfaceTop::class);
    }
}
