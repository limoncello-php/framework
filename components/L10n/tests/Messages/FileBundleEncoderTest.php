<?php namespace Limoncello\Tests\l10n\Messages;

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

use Limoncello\l10n\Messages\BundleStorage;
use Limoncello\l10n\Messages\FileBundleEncoder;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\l10n
 */
class FileBundleEncoderTest extends TestCase
{
    /**
     * Test load resources from files.
     */
    public function testLoadResources(): void
    {
        $encoder = new FileBundleEncoder(null, __DIR__ . DIRECTORY_SEPARATOR . 'Resources');

        $storageData = $encoder->getStorageData('en');
        $this->assertEquals([
            BundleStorage::INDEX_DEFAULT_LOCALE => 'en',
            BundleStorage::INDEX_DATA           => [
                'de' => [
                    'Messages' => [
                        'Hello World' => ['Hallo Welt', 'de'],
                    ],
                ],
                'de_AT' => [
                    'Messages' => [
                        'Hello World' => ['Hallo Welt aus Österreich', 'de_AT'],
                    ],
                ],
            ],
        ], $storageData);

        $storage = new BundleStorage($storageData);
        $this->assertNull($storage->get('en_US', 'Messages', 'Hello World'));
        $this->assertEquals(['Hallo Welt', 'de'], $storage->get('de_DE', 'Messages', 'Hello World'));
    }

    /**
     * Test load resources from message descriptions.
     */
    public function testLoadMessageDescriptions(): void
    {
        $messageDescriptions = [
            ['en_US', 'Messages', EnUsTestMessages::class],
        ];

        $encoder = new FileBundleEncoder($messageDescriptions, __DIR__ . DIRECTORY_SEPARATOR . 'Resources');
        $storage = new BundleStorage($encoder->getStorageData('en'));
        $this->assertEquals(['Hello World from US.', 'en_US'], $storage->get('en_US', 'Messages', 'Hello World'));
    }
}
