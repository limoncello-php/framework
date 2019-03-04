<?php declare (strict_types = 1);

namespace Limoncello\l10n\Messages;

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

use Limoncello\l10n\Contracts\Messages\ResourceBundleInterface;
use function array_keys;
use function assert;
use function is_scalar;
use function is_string;
use function locale_canonicalize;
use function strlen;

/**
 * @package Limoncello\l10n
 */
class ResourceBundle implements ResourceBundleInterface
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
     * @var array
     */
    private $properties;

    /**
     * @param string $locale
     * @param string $namespace
     * @param array  $properties
     */
    public function __construct(string $locale, string $namespace, array $properties)
    {
        $this->setLocale($locale)->setNamespace($namespace)->setProperties($properties);
    }

    /**
     * @inheritdoc
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @inheritdoc
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @inheritdoc
     */
    public function getKeys(): array
    {
        return array_keys($this->getProperties());
    }

    /**
     * @inheritdoc
     */
    public function getValue(string $key): string
    {
        $properties = $this->getProperties();

        return $properties[$key];
    }

    /**
     * @param string $locale
     *
     * @return self
     */
    public function setLocale(string $locale): self
    {
        assert(empty($locale) === false && locale_canonicalize($locale) === $locale);

        $this->locale = $locale;

        return $this;
    }

    /**
     * @param string $namespace
     *
     * @return self
     */
    public function setNamespace(string $namespace): self
    {
        assert(empty($namespace) === false);

        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @param array $properties
     *
     * @return self
     */
    public function setProperties(array $properties): self
    {
        // check all keys and values are non-empty strings
        $this->properties = [];
        foreach ($properties as $key => $value) {
            assert(is_scalar($key) === true && strlen((string)$key) > 0);
            assert(is_string($value) === true && strlen($value) > 0);
            $this->properties[(string)$key] = (string)$value;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
