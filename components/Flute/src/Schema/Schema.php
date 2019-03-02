<?php declare (strict_types = 1);

namespace Limoncello\Flute\Schema;

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

use Limoncello\Contracts\Application\ModelInterface;
use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemasInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryParserInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;
use Neomerx\JsonApi\Schema\BaseSchema;
use Neomerx\JsonApi\Schema\Identifier;

/**
 * @package Limoncello\Flute
 */
abstract class Schema extends BaseSchema implements SchemaInterface
{
    /**
     * @var ModelSchemaInfoInterface
     */
    private $modelSchemas;

    /**
     * @var JsonSchemasInterface
     */
    private $jsonSchemas;

    /**
     * @var array|null
     */
    private $attributesMapping;

    /**
     * @var array|null
     */
    private $relationshipsMapping;

    /**
     * @param FactoryInterface         $factory
     * @param JsonSchemasInterface     $jsonSchemas
     * @param ModelSchemaInfoInterface $modelSchemas
     */
    public function __construct(
        FactoryInterface $factory,
        JsonSchemasInterface $jsonSchemas,
        ModelSchemaInfoInterface $modelSchemas
    ) {
        assert(empty(static::TYPE) === false);
        assert(empty(static::MODEL) === false);

        parent::__construct($factory);

        $this->modelSchemas = $modelSchemas;
        $this->jsonSchemas  = $jsonSchemas;

        $this->attributesMapping    = null;
        $this->relationshipsMapping = null;
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return static::TYPE;
    }

    /**
     * @inheritdoc
     */
    public static function getAttributeMapping(string $jsonName): string
    {
        return static::getMappings()[static::SCHEMA_ATTRIBUTES][$jsonName];
    }

    /**
     * @inheritdoc
     */
    public static function getRelationshipMapping(string $jsonName): string
    {
        return static::getMappings()[static::SCHEMA_RELATIONSHIPS][$jsonName];
    }

    /**
     * @inheritdoc
     */
    public static function hasAttributeMapping(string $jsonName): bool
    {
        $mappings = static::getMappings();

        return
            array_key_exists(static::SCHEMA_ATTRIBUTES, $mappings) === true &&
            array_key_exists($jsonName, $mappings[static::SCHEMA_ATTRIBUTES]) === true;
    }

    /**
     * @inheritdoc
     */
    public static function hasRelationshipMapping(string $jsonName): bool
    {
        $mappings = static::getMappings();

        return
            array_key_exists(static::SCHEMA_RELATIONSHIPS, $mappings) === true &&
            array_key_exists($jsonName, $mappings[static::SCHEMA_RELATIONSHIPS]) === true;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes($model): iterable
    {
        foreach ($this->getAttributesMapping() as $jsonAttrName => $modelAttrName) {
            if ($this->hasProperty($model, $modelAttrName) === true) {
                yield $jsonAttrName => $model->{$modelAttrName};
            }
        }
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function getRelationships($model): iterable
    {
        assert($model instanceof ModelInterface);

        foreach ($this->getRelationshipsMapping() as $jsonRelName => [$modelRelName, $belongsToFkName, $reverseType]) {
            // if model has relationship data then use it
            if ($this->hasProperty($model, $modelRelName) === true) {
                yield $jsonRelName => $this->createRelationshipRepresentationFromData(
                    $model,
                    $modelRelName,
                    $jsonRelName
                );
                continue;
            }

            // if relationship is `belongs-to` and has that ID we can add relationship as identifier
            if ($belongsToFkName !== null && $this->hasProperty($model, $belongsToFkName) === true) {
                $reverseIndex = $model->{$belongsToFkName};
                $identifier   = $reverseIndex === null ?
                    null : new Identifier((string)$reverseIndex, $reverseType, false, null);

                yield $jsonRelName => [
                    static::RELATIONSHIP_DATA       => $identifier,
                    static::RELATIONSHIP_LINKS_SELF => $this->isAddSelfLinkInRelationshipWithData($jsonRelName),
                ];
                continue;
            }

            // if we are here it's nothing left but show relationship as a link
            yield $jsonRelName => [static::RELATIONSHIP_LINKS_SELF => true];
        }
    }

    /**
     * @inheritdoc
     */
    public function isAddSelfLinkInRelationshipWithData(string $relationshipName): bool
    {
        return false;
    }

    /**
     * @return ModelSchemaInfoInterface
     */
    protected function getModelSchemas(): ModelSchemaInfoInterface
    {
        return $this->modelSchemas;
    }

    /**
     * @return JsonSchemasInterface
     */
    protected function getJsonSchemas(): JsonSchemasInterface
    {
        return $this->jsonSchemas;
    }

    /**
     * @param ModelInterface $model
     * @param string         $modelRelName
     * @param string         $jsonRelName
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function createRelationshipRepresentationFromData(
        ModelInterface $model,
        string $modelRelName,
        string $jsonRelName
    ): array {
        assert($this->hasProperty($model, $modelRelName) === true);
        $relationshipData = $model->{$modelRelName};
        $isPaginatedData  = $relationshipData instanceof PaginatedDataInterface;

        $description = [static::RELATIONSHIP_LINKS_SELF => $this->isAddSelfLinkInRelationshipWithData($jsonRelName)];

        if ($isPaginatedData === false) {
            $description[static::RELATIONSHIP_DATA] = $relationshipData;

            return $description;
        }

        assert($relationshipData instanceof PaginatedDataInterface);

        $description[static::RELATIONSHIP_DATA] = $relationshipData->getData();

        if ($relationshipData->hasMoreItems() === false) {
            return $description;
        }

        // if we are here then relationship contains paginated data, so we have to add pagination links
        $offset    = $relationshipData->getOffset();
        $limit     = $relationshipData->getLimit();
        $urlPrefix = $this->getRelationshipSelfSubUrl($model, $jsonRelName) . '?';
        $buildLink = function (int $offset, int $limit) use ($urlPrefix) : LinkInterface {
            $paramsWithPaging = [
                JsonApiQueryParserInterface::PARAM_PAGING_OFFSET => $offset,
                JsonApiQueryParserInterface::PARAM_PAGING_LIMIT  => $limit,
            ];

            $subUrl = $urlPrefix . http_build_query($paramsWithPaging);

            return $this->getFactory()->createLink(true, $subUrl, false);
        };

        $nextOffset = $offset + $limit;
        $nextLimit  = $limit;
        if ($offset <= 0) {
            $description[static::RELATIONSHIP_LINKS] = [
                DocumentInterface::KEYWORD_NEXT => $buildLink($nextOffset, $nextLimit),
            ];
        } else {
            $prevOffset = $offset - $limit;
            if ($prevOffset < 0) {
                // set offset 0 and decrease limit
                $prevLimit  = $limit + $prevOffset;
                $prevOffset = 0;
            } else {
                $prevLimit = $limit;
            }
            $description[static::RELATIONSHIP_LINKS] = [
                DocumentInterface::KEYWORD_PREV => $buildLink($prevOffset, $prevLimit),
                DocumentInterface::KEYWORD_NEXT => $buildLink($nextOffset, $nextLimit),
            ];
        }

        return $description;
    }

    /**
     * @return array
     */
    private function getAttributesMapping(): array
    {
        if ($this->attributesMapping !== null) {
            return $this->attributesMapping;
        }

        $attributesMapping = static::getMappings()[static::SCHEMA_ATTRIBUTES] ?? [];

        // `id` is a `special` attribute and cannot be included in JSON API resource
        unset($attributesMapping[static::RESOURCE_ID]);

        $this->attributesMapping = $attributesMapping;

        return $this->attributesMapping;
    }

    /**
     * @return array
     */
    private function getRelationshipsMapping(): array
    {
        if ($this->relationshipsMapping !== null) {
            return $this->relationshipsMapping;
        }

        $relationshipsMapping = [];
        foreach (static::getMappings()[static::SCHEMA_RELATIONSHIPS] ?? [] as $jsonRelName => $modelRelName) {
            $belongsToFkName = null;
            $reverseJsonType = null;

            $relType = $this->getModelSchemas()->getRelationshipType(static::MODEL, $modelRelName);
            if ($relType === RelationshipTypes::BELONGS_TO) {
                $belongsToFkName = $this->getModelSchemas()->getForeignKey(static::MODEL, $modelRelName);
                $reverseSchema   = $this->getJsonSchemas()
                    ->getRelationshipSchema(static::class, $jsonRelName);
                $reverseJsonType = $reverseSchema->getType();
            }

            $relationshipsMapping[$jsonRelName] = [$modelRelName, $belongsToFkName, $reverseJsonType];
        }

        $this->relationshipsMapping = $relationshipsMapping;

        return $this->relationshipsMapping;
    }

    /**
     * @param ModelInterface $model
     * @param string         $name
     *
     * @return bool
     */
    private function hasProperty(ModelInterface $model, string $name): bool
    {
        $hasRelationship = property_exists($model, $name);

        return $hasRelationship;
    }

    /**
     * @param ModelInterface $model
     * @param string         $jsonRelName
     *
     * @return string
     */
    private function getRelationshipSelfSubUrl(ModelInterface $model, string $jsonRelName): string
    {
        return $this->getSelfSubUrl($model) . '/relationships/' . $jsonRelName;
    }
}
