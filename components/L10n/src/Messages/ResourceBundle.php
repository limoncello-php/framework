<?php namespace Limoncello\l10n\Messages;

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

use Limoncello\l10n\Contracts\Messages\ResourceBundleInterface;

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
    public function getValue($key): string
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
        assert(is_string($locale) === true && empty($locale) === false && locale_canonicalize($locale) === $locale);

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
        assert(is_string($namespace) === true && empty($namespace) === false);

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
        assert(call_user_func(function () use ($properties) {
            $result = true;
            foreach ($properties as $key => $value) {
                $result = $result === true &&
                    is_string($key) === true && empty($key) === false &&
                    is_string($value) === true && empty($value) === false;
            }
            return $result;
        }) === true);

        $this->properties = $properties;

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
