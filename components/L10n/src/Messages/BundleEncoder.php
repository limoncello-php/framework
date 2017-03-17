<?php namespace Limoncello\l10n\Messages;

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

use Limoncello\l10n\Contracts\Messages\BundleEncoderInterface;
use Limoncello\l10n\Contracts\Messages\BundleStorageInterface;
use Limoncello\l10n\Contracts\Messages\ResourceBundleInterface;

/**
 * @package Limoncello\l10n
 */
class BundleEncoder implements BundleEncoderInterface
{
    /**
     * @var array
     */
    private $bundles = [];

    /**
     * @inheritdoc
     */
    public function addBundle(ResourceBundleInterface $bundle)
    {
        assert($this->hasLocalizedNamespace($bundle->getLocale(), $bundle->getNamespace()) === false);

        $this->bundles[$bundle->getLocale()][$bundle->getNamespace()] = $bundle;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStorageData($defaultLocale)
    {
        $defaultNamespaces = $this->getNamespaces($defaultLocale);

        $data = [];
        foreach ($this->getLocales() as $locale) {
            $localizedNamespaces = $this->getNamespaces($locale);
            $combinedNamespaces  = $defaultNamespaces + $localizedNamespaces;
            foreach ($combinedNamespaces as $namespace) {
                $bundle = $this->getBundle($locale, $namespace);
                $bundle = $bundle !== null ? $bundle : $this->getBundle($defaultLocale, $namespace);
                $data[$locale][$namespace] = $defaultLocale === $locale ? $this->encodeBundle($bundle) :
                    $this->encodeMergedBundles($bundle, $this->getBundle($defaultLocale, $namespace));
            }
        }

        return [
            BundleStorage::INDEX_DEFAULT_LOCALE => $defaultLocale,
            BundleStorage::INDEX_DATA           => $data
        ];
    }

    /**
     * @return array
     */
    protected function getBundles()
    {
        return $this->bundles;
    }

    /**
     * @param ResourceBundleInterface $bundle
     *
     * @return array
     */
    private function encodeBundle(ResourceBundleInterface $bundle)
    {
        $encodedBundle = [];
        foreach ($bundle->getKeys() as $key) {
            $encodedBundle[$key] = $this->getBundleValue($bundle, $key);
        }

        return $encodedBundle;
    }

    /**
     * @param string $locale
     * @param string $namespace
     *
     * @return null|ResourceBundleInterface
     */
    private function getBundle($locale, $namespace)
    {
        $this->assertLocale($locale);
        $this->assertNamespace($namespace);

        $bundles   = $this->getBundles();
        $hasBundle = isset($bundles[$locale][$namespace]) === true;
        $result    = $hasBundle === true ? $bundles[$locale][$namespace] : null;

        return $result;
    }

    /**
     * @param ResourceBundleInterface      $localizedBundle
     * @param ResourceBundleInterface|null $defaultBundle
     *
     * @return array
     */
    private function encodeMergedBundles(
        ResourceBundleInterface $localizedBundle,
        ResourceBundleInterface $defaultBundle = null
    ) {
        if ($defaultBundle === null) {
            // there is no default bundle for this localized one
            return $this->encodeBundle($localizedBundle);
        }

        $localizedKeys = $localizedBundle->getKeys();
        $defaultKeys   = $defaultBundle->getKeys();

        $commonKeys        = array_intersect($localizedKeys, $defaultKeys);
        $localizedOnlyKeys = array_diff($localizedKeys, $commonKeys);
        $defaultOnlyKeys   = array_diff($defaultKeys, $commonKeys);

        $encodedBundle = [];

        foreach ($commonKeys as $key) {
            $encodedBundle[$key] = $this->getBundleValue($localizedBundle, $key);
        }
        foreach ($localizedOnlyKeys as $key) {
            $encodedBundle[$key] = $this->getBundleValue($localizedBundle, $key);
        }
        foreach ($defaultOnlyKeys as $key) {
            $encodedBundle[$key] = $this->getBundleValue($defaultBundle, $key);
        }

        return $encodedBundle;
    }

    /**
     * @return array
     */
    private function getLocales()
    {
        return array_keys($this->getBundles());
    }

    /**
     * @param string $locale
     *
     * @return bool
     */
    private function hasLocale($locale)
    {
        $this->assertLocale($locale);
        $result = in_array($locale, $this->getLocales());

        return $result;
    }

    /**
     * @param string $locale
     *
     * @return string[]
     */
    private function getNamespaces($locale)
    {
        $result = $this->hasLocale($locale) === true ? array_keys($this->getBundles()[$locale]) : [];

        return $result;
    }

    /**
     * @param string $locale
     * @param string $namespace
     *
     * @return bool
     */
    private function hasLocalizedNamespace($locale, $namespace)
    {
        $this->assertNamespace($namespace);

        $result = in_array($namespace, $this->getNamespaces($locale));

        return $result;
    }

    /**
     * @param string $locale
     *
     * @return void
     */
    private function assertLocale($locale)
    {
        assert(is_string($locale) === true && empty($locale) === false);
    }

    /**
     * @param string $namespace
     *
     * @return void
     */
    private function assertNamespace($namespace)
    {
        assert(is_string($namespace) === true && empty($namespace) === false);
    }

    /**
     * @param ResourceBundleInterface $bundle
     * @param string                  $key
     *
     * @return string[]
     */
    private function getBundleValue(ResourceBundleInterface $bundle, $key)
    {
        return [
            BundleStorageInterface::INDEX_PAIR_VALUE  => $bundle->getValue($key),
            BundleStorageInterface::INDEX_PAIR_LOCALE => $bundle->getLocale(),
        ];
    }
}
