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

/**
 * @package Limoncello\Tests\l10n
 */
class BundleStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test basic get and set operations.
     */
    public function testGetAndSet()
    {
        $messages = [
            'Key as a readable text' => ['Lets assume it would be german translation', 'de'],
            'key_as_an_id'           => ['And that would be another german translation', 'de'],
        ];

        $storage = new BundleStorage([
            BundleStorage::INDEX_DEFAULT_LOCALE => 'en',
            BundleStorage::INDEX_DATA           => [
                'de' => [
                    'ErrorMessages' => $messages
                ],
            ],
        ]);

        $this->assertTrue($storage->has('de', 'ErrorMessages', 'key_as_an_id'));
        $this->assertFalse($storage->has('en', 'ErrorMessages', 'key_as_an_id'));
        $this->assertFalse($storage->has('de', 'ErrorMessages', 'non-existing key'));
        // Note that de-AT or de_AT would be handled successfully
        $this->assertEquals(
            ['And that would be another german translation', 'de'],
            $storage->get('de-AT', 'ErrorMessages', 'key_as_an_id')
        );
        $this->assertNull($storage->get('de_DE', 'ErrorMessages', 'Non existing text passed through'));
        $this->assertTrue($storage->hasResources('de', 'ErrorMessages'));
        $this->assertFalse($storage->hasResources('en', 'ErrorMessages'));
        $this->assertEquals($messages, $storage->getResources('de_DE', 'ErrorMessages'));
    }
}
