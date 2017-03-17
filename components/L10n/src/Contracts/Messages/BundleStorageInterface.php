<?php namespace Limoncello\l10n\Contracts\Messages;

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

/**
 * @package Limoncello\l10n
 */
interface BundleStorageInterface
{
    /** Encode index */
    const INDEX_PAIR_VALUE = 0;

    /** Encode index */
    const INDEX_PAIR_LOCALE = self::INDEX_PAIR_VALUE + 1;

    /**
     * @return string
     */
    public function getDefaultLocale();

    /**
     * @param string $locale
     * @param string $namespace
     * @param string $key
     *
     * @return bool
     */
    public function has($locale, $namespace, $key);

    /**
     * @param string $locale
     * @param string $namespace
     * @param string $key
     *
     * @return array|null
     */
    public function get($locale, $namespace, $key);

    /**
     * @param string $locale
     * @param string $namespace
     *
     * @return bool
     */
    public function hasResources($locale, $namespace);

    /**
     * @param string $locale
     * @param string $namespace
     *
     * @return array
     */
    public function getResources($locale, $namespace);
}
