<?php namespace Limoncello\Tests\Flute\Data\L10n;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\l10n\Format\Formatter;
use Limoncello\l10n\Format\Translator;
use Limoncello\l10n\Messages\BundleStorage;
use Limoncello\l10n\Messages\FileBundleEncoder;

/**
 * @package Limoncello\Tests\Flute
 */
class FormatterFactory implements FormatterFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createFormatter(string $namespace): FormatterInterface
    {
        return $this->createFormatterForLocale($namespace, 'En');
    }

    /**
     * @inheritdoc
     */
    public function createFormatterForLocale(string $namespace, string $locale): FormatterInterface
    {
        $dirPath   = implode(DIRECTORY_SEPARATOR, [__DIR__, 'res', 'Messages']);
        $storage   = (new FileBundleEncoder(null, $dirPath))->getStorageData('En');
        $formatter = new Formatter($locale, $namespace, new Translator(new BundleStorage($storage)));

        return $formatter;
    }
}
