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

/**
 * @package Limoncello\Tests\l10n
 */
class TranslatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test translate.
     */
    public function testTranslate()
    {
        $encoder     = new FileBundleEncoder(
            __DIR__ . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            'Messages' . DIRECTORY_SEPARATOR .
            'Resources'
        );
        $storageData = $encoder->getStorageData('en');
        $storage     = new BundleStorage($storageData);

        /** @var TranslatorInterface $translator */
        $translator = new Translator($storage);

        $this->assertEquals('Hello World', $translator->translateMessage('en_US', 'Messages', 'Hello World'));
        $this->assertEquals('Hallo Welt', $translator->translateMessage('de_AT', 'Messages', 'Hello World'));
    }
}
