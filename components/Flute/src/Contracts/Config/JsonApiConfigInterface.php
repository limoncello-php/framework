<?php namespace Limoncello\Flute\Contracts\Config;

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

/**
 * @package Limoncello\Flute
 */
interface JsonApiConfigInterface
{
    /** Config key */
    const KEY_MODEL_TO_SCHEMA_MAP = 0;

    /** Config key */
    const KEY_JSON = self::KEY_MODEL_TO_SCHEMA_MAP + 1;

    /** Config key */
    const KEY_JSON_RELATIONSHIP_PAGING_SIZE = 0;

    /** Config key */
    const KEY_JSON_OPTIONS = self::KEY_JSON_RELATIONSHIP_PAGING_SIZE + 1;

    /** Config key */
    const KEY_JSON_DEPTH = self::KEY_JSON_OPTIONS + 1;

    /** Config key */
    const KEY_JSON_IS_SHOW_VERSION = self::KEY_JSON_DEPTH + 1;

    /** Config key */
    const KEY_JSON_VERSION_META = self::KEY_JSON_IS_SHOW_VERSION + 1;

    /** Config key */
    const KEY_JSON_URL_PREFIX = self::KEY_JSON_VERSION_META + 1;

    /**
     * @return array
     */
    public function getModelSchemaMap();

    /**
     * @param array $modelSchemaMap
     *
     * @return $this
     */
    public function setModelSchemaMap(array $modelSchemaMap);

    /**
     * @return int
     */
    public function getJsonEncodeOptions();

    /**
     * @param int $options
     *
     * @return $this
     */
    public function setJsonEncodeOptions($options);

    /**
     * @return int
     */
    public function getJsonEncodeDepth();

    /**
     * @param int $depth
     *
     * @return $this
     */
    public function setJsonEncodeDepth($depth);

    /**
     * @return boolean
     */
    public function isShowVersion();

    /**
     * @return $this
     */
    public function setShowVersion();

    /**
     * @return $this
     */
    public function setHideVersion();

    /**
     * @return mixed
     */
    public function getMeta();

    /**
     * @param mixed $meta
     *
     * @return $this
     */
    public function setMeta($meta);

    /**
     * @return string
     */
    public function getUriPrefix();

    /**
     * @param string $prefix
     *
     * @return $this
     */
    public function setUriPrefix($prefix);

    /**
     * @return int
     */
    public function getRelationshipPagingSize();

    /**
     * @param int $size
     *
     * @return $this
     */
    public function setRelationshipPagingSize($size);

    /**
     * @return array
     */
    public function getConfig();

    /**
     * @param array $data
     *
     * @return JsonApiConfigInterface
     */
    public function setConfig(array $data);
}
