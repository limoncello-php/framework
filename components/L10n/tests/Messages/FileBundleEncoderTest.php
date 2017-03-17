<?php namespace Limoncello\Tests\l10n\Messages;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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

use Limoncello\l10n\Messages\BundleStorage;
use Limoncello\l10n\Messages\FileBundleEncoder;

/**
 * @package Limoncello\Tests\l10n
 */
class FileBundleEncoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test load resources from files.
     */
    public function testLoadResources()
    {
        $encoder = new FileBundleEncoder(__DIR__ . DIRECTORY_SEPARATOR . 'Resources');

        $storageData = $encoder->getStorageData('en');
        $this->assertEquals([
            BundleStorage::INDEX_DEFAULT_LOCALE => 'en',
            BundleStorage::INDEX_DATA           => [
                'de' => [
                    'Messages' => [
                        'Hello World' => ['Hallo Welt', 'de'],
                    ],
                ],
            ],
        ], $storageData);

        $storage = new BundleStorage($storageData);
        $this->assertNull($storage->get('en_US', 'Messages', 'Hello World'));
        $this->assertEquals(['Hallo Welt', 'de'], $storage->get('de_DE', 'Messages', 'Hello World'));
    }
}
