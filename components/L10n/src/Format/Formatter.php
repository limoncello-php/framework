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

use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\l10n\Contracts\Format\TranslatorInterface;
use function assert;

/**
 * @package Limoncello\l10n
 */
class Formatter implements FormatterInterface
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param string              $locale
     * @param string              $namespace
     * @param TranslatorInterface $translator
     */
    public function __construct(string $locale, string $namespace, TranslatorInterface $translator)
    {
        assert(empty($locale) === false && empty($namespace) === false);

        $this->locale     = locale_canonicalize($locale);
        $this->namespace  = $namespace;
        $this->translator = $translator;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @inheritdoc
     */
    public function formatMessage(string $message, array $args = []): string
    {
        $result = $this->getTranslator()->translateMessage($this->getLocale(), $this->getNamespace(), $message, $args);

        return $result;
    }
}
