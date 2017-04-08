<?php namespace Limoncello\Flute\Config;

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

use Limoncello\Flute\Contracts\Config\JsonApiConfigInterface;

/**
 * @package Limoncello\Flute
 */
class JsonApiConfig implements JsonApiConfigInterface
{
    /**
     * @var array
     */
    private $modelSchemaMap = [];

    /**
     * @var int
     */
    private $options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES;

    /**
     * @var int
     */
    private $depth = 512;

    /**
     * @var bool
     */
    private $isShowVersion = false;

    /**
     * @var mixed
     */
    private $meta = null;

    /**
     * @var string|null
     */
    private $uriPrefix = null;

    /**
     * @var int
     */
    private $pagingSize = 20;

    /**
     * @inheritdoc
     */
    public function getJsonEncodeOptions(): int
    {
        return $this->options;
    }

    /**
     * @inheritdoc
     */
    public function setJsonEncodeOptions(int $options): JsonApiConfigInterface
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getJsonEncodeDepth(): int
    {
        return $this->depth;
    }

    /**
     * @inheritdoc
     */
    public function setJsonEncodeDepth(int $depth): JsonApiConfigInterface
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isShowVersion(): bool
    {
        return $this->isShowVersion;
    }

    /**
     * @inheritdoc
     */
    public function setShowVersion(): JsonApiConfigInterface
    {
        $this->isShowVersion = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setHideVersion(): JsonApiConfigInterface
    {
        $this->isShowVersion = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @inheritdoc
     */
    public function setMeta($meta): JsonApiConfigInterface
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUriPrefix()
    {
        return $this->uriPrefix;
    }

    /**
     * @inheritdoc
     */
    public function setUriPrefix(string $prefix = null): JsonApiConfigInterface
    {
        $this->uriPrefix = $prefix;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipPagingSize(): int
    {
        return $this->pagingSize;
    }

    /**
     * @inheritdoc
     */
    public function setRelationshipPagingSize(int $size): JsonApiConfigInterface
    {
        $this->pagingSize = $size;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getModelSchemaMap(): array
    {
        return $this->modelSchemaMap;
    }

    /**
     * @inheritdoc
     */
    public function setModelSchemaMap(array $modelSchemaMap): JsonApiConfigInterface
    {
        $this->modelSchemaMap = $modelSchemaMap;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return [
            self::KEY_MODEL_TO_SCHEMA_MAP => $this->modelSchemaMap,

            self::KEY_JSON => [
                self::KEY_JSON_RELATIONSHIP_PAGING_SIZE => $this->getRelationshipPagingSize(),
                self::KEY_JSON_OPTIONS                  => $this->getJsonEncodeOptions(),
                self::KEY_JSON_DEPTH                    => $this->getJsonEncodeDepth(),
                self::KEY_JSON_IS_SHOW_VERSION          => $this->isShowVersion(),
                self::KEY_JSON_VERSION_META             => $this->getMeta(),
                self::KEY_JSON_URL_PREFIX               => $this->getUriPrefix(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function setConfig(array $data): JsonApiConfigInterface
    {
        $this->setModelSchemaMap($data[self::KEY_MODEL_TO_SCHEMA_MAP]);

        $jsonSection = $data[self::KEY_JSON];
        $this->setRelationshipPagingSize($jsonSection[self::KEY_JSON_RELATIONSHIP_PAGING_SIZE]);
        $this->setJsonEncodeOptions($jsonSection[self::KEY_JSON_OPTIONS]);
        $this->setJsonEncodeDepth($jsonSection[self::KEY_JSON_DEPTH]);
        $jsonSection[self::KEY_JSON_IS_SHOW_VERSION] === true ? $this->setShowVersion() : $this->setHideVersion();
        $this->setMeta($jsonSection[self::KEY_JSON_VERSION_META]);
        $this->setUriPrefix($jsonSection[self::KEY_JSON_URL_PREFIX]);

        return $this;
    }
}
