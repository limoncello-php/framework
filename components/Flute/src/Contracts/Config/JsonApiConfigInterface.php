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

/** @deprecated
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
    public function getModelSchemaMap(): array;

    /**
     * @param array $modelSchemaMap
     *
     * @return self
     */
    public function setModelSchemaMap(array $modelSchemaMap): self;

    /**
     * @return int
     */
    public function getJsonEncodeOptions(): int;

    /**
     * @param int $options
     *
     * @return self
     */
    public function setJsonEncodeOptions(int $options): self;

    /**
     * @return int
     */
    public function getJsonEncodeDepth(): int;

    /**
     * @param int $depth
     *
     * @return self
     */
    public function setJsonEncodeDepth(int $depth): self;

    /**
     * @return bool
     */
    public function isShowVersion(): bool;

    /**
     * @return self
     */
    public function setShowVersion(): self;

    /**
     * @return self
     */
    public function setHideVersion(): self;

    /**
     * @return mixed
     */
    public function getMeta();

    /**
     * @param mixed $meta
     *
     * @return self
     */
    public function setMeta($meta): self;

    /**
     * @return string|null
     */
    public function getUriPrefix();

    /**
     * @param string $prefix
     *
     * @return self
     */
    public function setUriPrefix(string $prefix = null): self;

    /**
     * @return int
     */
    public function getRelationshipPagingSize(): int;

    /**
     * @param int $size
     *
     * @return self
     */
    public function setRelationshipPagingSize(int $size): self;

    /**
     * @return array
     */
    public function getConfig(): array;

    /**
     * @param array $data
     *
     * @return self
     */
    public function setConfig(array $data): self;
}
