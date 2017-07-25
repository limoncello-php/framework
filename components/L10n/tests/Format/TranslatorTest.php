<?php namespace Limoncello\Tests\l10n\Format;

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

use Limoncello\l10n\Contracts\Format\TranslatorInterface;
use Limoncello\l10n\Format\Translator;
use Limoncello\l10n\Messages\BundleStorage;
use Limoncello\l10n\Messages\FileBundleEncoder;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\l10n
 */
class TranslatorTest extends TestCase
{
    const RESOURCES_DIR =
        __DIR__ . DIRECTORY_SEPARATOR .
        '..' . DIRECTORY_SEPARATOR .
        'Messages' . DIRECTORY_SEPARATOR .
        'Resources';

    /**
     * Test translate.
     */
    public function testTranslateWithNonExistingDefault()
    {
        $storageData = (new FileBundleEncoder(static::RESOURCES_DIR))->getStorageData('en');

        /** @var TranslatorInterface $translator */
        $translator = new Translator(new BundleStorage($storageData));

        $this->assertEquals('Hello World', $translator->translateMessage('en_US', 'Messages', 'Hello World'));
        $this->assertEquals('Hallo Welt', $translator->translateMessage('DE', 'Messages', 'Hello World'));
        $this->assertEquals('Hallo Welt', $translator->translateMessage('dE_Lu', 'Messages', 'Hello World'));
        $this->assertEquals(
            'Hallo Welt aus Österreich',
            $translator->translateMessage('de_AT', 'Messages', 'Hello World')
        );
        $this->assertEquals('Good morning', $translator->translateMessage('de', 'Messages', 'Good morning'));
    }

    /**
     * Test translate.
     */
    public function testTranslateWithExistingDefault()
    {
        $storageData = (new FileBundleEncoder(static::RESOURCES_DIR))->getStorageData('de');

        /** @var TranslatorInterface $translator */
        $translator = new Translator(new BundleStorage($storageData));

        // That might look odd but what is happening: we don't have any resources in `en_*` so it falls back to `de`.
        // 'Hello World' is just a string key and the code of course doesn't know if it's in English. It's just a key.
        // Then it searches a value for that key in `de` resources and finds it.
        $this->assertEquals('Hallo Welt', $translator->translateMessage('en_US', 'Messages', 'Hello World'));

        // Same story here but we don't have any values for a key 'Guten Morgen' so it returns the key itself.
        $this->assertEquals('Guten Morgen', $translator->translateMessage('en_US', 'Messages', 'Guten Morgen'));

        $this->assertEquals('Good morning', $translator->translateMessage('en_US', 'Messages', 'Good morning'));

        $this->assertEquals('Good morning', $translator->translateMessage('de', 'Messages', 'Good morning'));

        // this one uses existing `de` resources
        $this->assertEquals('Hallo Welt', $translator->translateMessage('DE', 'Messages', 'Hello World'));

        $this->assertEquals('Hallo Welt', $translator->translateMessage('dE_Lu', 'Messages', 'Hello World'));
        $this->assertEquals(
            'Hallo Welt aus Österreich',
            $translator->translateMessage('de_AT', 'Messages', 'Hello World')
        );
    }
}
