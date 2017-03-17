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

use Limoncello\l10n\Messages\BundleEncoder;
use Limoncello\l10n\Messages\BundleStorage;
use Limoncello\l10n\Messages\ResourceBundle;

/**
 * @package Limoncello\Tests\l10n
 */
class BundleEncoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test basic get and set operations.
     */
    public function testGetAndSet()
    {
        $encoder = new BundleEncoder();
        $encoder->addBundle(new ResourceBundle('en_US', 'ErrorMessages', [
            'Key as a readable text' => "That's an original readable text.",
            'key_as_an_id'           => "That's an original text by id.",
            'en_only_id'             => "This text is not translated.",
        ]));
        $encoder->addBundle(new ResourceBundle('de_DE', 'ErrorMessages', [
            'Key as a readable text' => 'Lets assume it would be german translation.',
            'key_as_an_id'           => 'And that would be another german translation.',
            'de_only_id'             => "This text is only in german.",
        ]));
        $encoder->addBundle(new ResourceBundle('de_DE', 'ExtraMessages', [
            'de_only_id2' => "This is a second text only in german.",
        ]));

        $storageData = $encoder->getStorageData('en_US');
        $this->assertEquals([
            BundleStorage::INDEX_DEFAULT_LOCALE => 'en_US',
            BundleStorage::INDEX_DATA           => [
                'en_US' => [
                    'ErrorMessages' => [
                        'Key as a readable text' => ['That\'s an original readable text.', 'en_US'],
                        'key_as_an_id'           => ['That\'s an original text by id.', 'en_US'],
                        'en_only_id'             => ['This text is not translated.', 'en_US'],
                    ],
                ],
                'de_DE' => [
                    'ErrorMessages' => [
                        'Key as a readable text' => ['Lets assume it would be german translation.', 'de_DE'],
                        'key_as_an_id'           => ['And that would be another german translation.', 'de_DE'],
                        'de_only_id'             => ['This text is only in german.', 'de_DE'],
                        'en_only_id'             => ['This text is not translated.', 'en_US'],
                    ],
                    'ExtraMessages' => [
                        'de_only_id2' => ['This is a second text only in german.', 'de_DE'],
                    ],
                ],
            ],
        ], $storageData);

        $storageData = $encoder->getStorageData('de_DE');
        $this->assertEquals([
            BundleStorage::INDEX_DEFAULT_LOCALE => 'de_DE',
            BundleStorage::INDEX_DATA           => [
                'en_US' => [
                    'ErrorMessages' => [
                        'Key as a readable text' => ['That\'s an original readable text.', 'en_US'],
                        'key_as_an_id'           => ['That\'s an original text by id.', 'en_US'],
                        'en_only_id'             => ['This text is not translated.', 'en_US'],
                        'de_only_id'             => ['This text is only in german.', 'de_DE'],
                    ],
                    'ExtraMessages' => [
                        'de_only_id2' => ['This is a second text only in german.', 'de_DE'],
                    ],
                ],
                'de_DE' => [
                    'ErrorMessages' => [
                        'Key as a readable text' => ['Lets assume it would be german translation.', 'de_DE'],
                        'key_as_an_id'           => ['And that would be another german translation.', 'de_DE'],
                        'de_only_id'             => ['This text is only in german.', 'de_DE'],
                    ],
                    'ExtraMessages' => [
                        'de_only_id2' => ['This is a second text only in german.', 'de_DE'],
                    ],
                ],
            ],
        ], $storageData);
    }
}
