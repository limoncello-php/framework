<?php namespace Limoncello\Flute\Schema;

/**
 * Copyright 2015-2018 info@neomerx.com
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

        parent::__construct($factory);

        $this->modelSchemas = $modelSchemas;
        $this->jsonSchemas  = $jsonSchemas;
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
        $attributes = [];
        $mappings   = static::getMappings();
        if (array_key_exists(static::SCHEMA_ATTRIBUTES, $mappings) === true) {
            $attrMappings = $mappings[static::SCHEMA_ATTRIBUTES];

            // `id` is a `special` attribute and cannot be included in JSON API resource
            unset($attrMappings[static::RESOURCE_ID]);

            foreach ($attrMappings as $jsonAttrName => $modelAttrName) {
                $attributes[$jsonAttrName] = $model->{$modelAttrName} ?? null;
            }
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function getRelationships($model): iterable
    {
        assert($model instanceof ModelInterface);

        $relationships = [];

        $mappings = static::getMappings();
        if (array_key_exists(static::SCHEMA_RELATIONSHIPS, $mappings) === true) {
            foreach ($mappings[static::SCHEMA_RELATIONSHIPS] as $jsonRelName => $modelRelName) {
                // if model has relationship data then use it
                if ($this->hasProperty($model, $modelRelName) === true) {
                    $relationships[$jsonRelName] = $this->createRelationshipRepresentationFromData(
                        $model,
                        $modelRelName,
                        $jsonRelName
                    );
                    continue;
                }

                // if relationship is `belongs-to` and has that ID we can add relationship as identifier
                $modelClass  = get_class($model);
                $relType = $this->getModelSchemas()->getRelationshipType($modelClass, $modelRelName);
                if ($relType === RelationshipTypes::BELONGS_TO) {
                    $fkName = $this->getModelSchemas()->getForeignKey($modelClass, $modelRelName);
                    if ($this->hasProperty($model, $fkName) === true) {
                        $reverseIndex  = $model->{$fkName};
                        if ($reverseIndex === null) {
                            $identifier = null;
                        } else {
                            $reverseSchema = $this->getJsonSchemas()
                                ->getRelationshipSchema(static::class, $jsonRelName);
                            $reverseType   = $reverseSchema->getType();
                            $identifier    = new Identifier($reverseIndex, $reverseType, false, null);
                        }
                        $relationships[$jsonRelName] = [
                            static::RELATIONSHIP_DATA       => $identifier,
                            static::RELATIONSHIP_LINKS_SELF => $this->isAddSelfLinkInRelationshipWithData($jsonRelName),
                        ];
                        continue;
                    }
                }

                // if we are here it's nothing left but show relationship as a link
                $relationships[$jsonRelName] = [static::RELATIONSHIP_LINKS_SELF => true];
            }
        }

        return $relationships;
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
