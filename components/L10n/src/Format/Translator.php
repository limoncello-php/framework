<?php declare (strict_types = 1);

namespace Limoncello\l10n\Format;

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

use Limoncello\l10n\Contracts\Format\TranslatorInterface;
use Limoncello\l10n\Contracts\Messages\BundleStorageInterface;
use MessageFormatter;

/**
 * @package Limoncello\l10n
 */
class Translator implements TranslatorInterface
{
    /**
     * @var BundleStorageInterface
     */
    private $storage;

    /**
     * @param BundleStorageInterface $storage
     */
    public function __construct(BundleStorageInterface $storage)
    {
        $this->setStorage($storage);
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function translateMessage(string $locale, string $namespace, string $message, array $args = []): string
    {
        $translation = $this->getStorage()->get($locale, $namespace, $message);
        if ($translation !== null) {
            $message = $translation[BundleStorageInterface::INDEX_PAIR_VALUE];
            $locale  = $translation[BundleStorageInterface::INDEX_PAIR_LOCALE];
        }

        return static::formatMessage($locale, $message, $args);
    }

    /**
     * @return BundleStorageInterface
     */
    public function getStorage(): BundleStorageInterface
    {
        return $this->storage;
    }

    /**
     * @param BundleStorageInterface $storage
     *
     * @return TranslatorInterface
     */
    public function setStorage(BundleStorageInterface $storage): TranslatorInterface
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * @param string $locale
     * @param string $message
     * @param array  $args
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected static function formatMessage(string $locale, string $message, array $args): string
    {
        return MessageFormatter::formatMessage($locale, $message, $args);
    }
}
