<?php namespace Limoncello\Flute\Schema;

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

use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryValidatingParserInterface;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Schema\BaseSchema;

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
     * @param FactoryInterface         $factory
     * @param ModelSchemaInfoInterface $modelSchemas
     */
    public function __construct(FactoryInterface $factory, ModelSchemaInfoInterface $modelSchemas)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $this->resourceType = static::TYPE;

        parent::__construct($factory);

        $this->modelSchemas = $modelSchemas;
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
    public function getAttributes($model, array $fieldKeysFilter = null): ?array
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

    /** @noinspection PhpMissingParentCallCommonInspection
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function getRelationships($model, bool $isPrimary, array $includeRelationships): ?array
    {
        $modelClass    = get_class($model);
        $relationships = [];
        $mappings      = static::getMappings();
        if (array_key_exists(static::SCHEMA_RELATIONSHIPS, $mappings) === true) {
            $relMappings = $mappings[static::SCHEMA_RELATIONSHIPS];
            foreach ($relMappings as $jsonRelName => $modelRelName) {
                $isRelToBeIncluded = array_key_exists($jsonRelName, $includeRelationships) === true;

                $hasRelData = $this->hasRelationship($model, $modelRelName);
                $relType    = $this->getModelSchemas()->getRelationshipType($modelClass, $modelRelName);

                $isShowAsLink = false;

                // there is a case for `to-1` relationship when we can return identity resource (type + id)
                if ($relType === RelationshipTypes::BELONGS_TO) {
                    if ($isRelToBeIncluded === true && $hasRelData === true) {
                        $relationships[$jsonRelName] = [static::DATA => $model->{$modelRelName}];
                        continue;
                    } else {
                        $schema = $this->getModelSchemas();

                        $class  = get_class($model);
                        $fkName = $schema->getForeignKey($class, $modelRelName);

                        $isShowAsLink = property_exists($model, $fkName) === false;

                        if ($isShowAsLink === false) {
                            // show as identity (type, id)

                            $identity       = null;
                            $reversePkValue = $model->{$fkName};
                            if ($reversePkValue !== null) {
                                $reverseClass  = $schema->getReverseModelClass($class, $modelRelName);
                                $reversePkName = $schema->getPrimaryKey($reverseClass);

                                $identity                   = new $reverseClass;
                                $identity->{$reversePkName} = $reversePkValue;
                            }

                            $relationships[$jsonRelName] = [
                                static::DATA  => $identity,
                                static::LINKS => $this->getRelationshipLinks($model, $jsonRelName),
                            ];
                            continue;
                        }

                        // the relationship will be shown as a link
                    }
                }

                // if our storage do not have any data for this relationship or relationship would not
                // be included we return it as link
                if ($hasRelData === false ||
                    ($isRelToBeIncluded === false && $relType !== RelationshipTypes::BELONGS_TO)
                ) {
                    $isShowAsLink = true;
                }

                if ($isShowAsLink === true) {
                    $relationships[$jsonRelName] = $this->getRelationshipLinkRepresentation($model, $jsonRelName);
                    continue;
                }

                // if we are here this is a `to-Many` relationship and we have to show data and got the data

                $relUri                      = $this->getRelationshipSelfUrl($model, $jsonRelName);
                $relationships[$jsonRelName] = $this->getRelationshipDescription($model->{$modelRelName}, $relUri);
            }
        }

        return $relationships;
    }

    /**
     * @return ModelSchemaInfoInterface
     */
    protected function getModelSchemas(): ModelSchemaInfoInterface
    {
        return $this->modelSchemas;
    }

    /**
     * @param PaginatedDataInterface $data
     * @param string                 $uri
     *
     * @return array
     */
    protected function getRelationshipDescription(PaginatedDataInterface $data, string $uri): array
    {
        if ($data->hasMoreItems() === false) {
            return [static::DATA => $data->getData()];
        }

        $buildUrl = function ($offset) use ($data, $uri) {
            $paramsWithPaging = [
                JsonApiQueryValidatingParserInterface::PARAM_PAGING_OFFSET => $offset,
                JsonApiQueryValidatingParserInterface::PARAM_PAGING_LIMIT  => $data->getLimit(),
            ];
            $fullUrl          = $uri . '?' . http_build_query($paramsWithPaging);

            return $fullUrl;
        };

        $links = [];

        // It looks like relationship can only hold first data rows so we might need `next` link but never `prev`

        if ($data->hasMoreItems() === true) {
            $offset                                 = $data->getOffset() + $data->getLimit();
            $links[DocumentInterface::KEYWORD_NEXT] = $this->createLink($buildUrl($offset));
        }

        return [
            static::DATA  => $data->getData(),
            static::LINKS => $links,
        ];
    }

    /**
     * @param mixed  $model
     * @param string $jsonRelationship
     *
     * @return array
     */
    protected function getRelationshipLinkRepresentation($model, string $jsonRelationship): array
    {
        return [
            static::LINKS     => $this->getRelationshipLinks($model, $jsonRelationship),
            static::SHOW_DATA => false,
        ];
    }

    /**
     * @param mixed  $model
     * @param string $jsonRelationship
     *
     * @return array
     */
    protected function getRelationshipLinks($model, string $jsonRelationship): array
    {
        $links = [];
        if ($this->showSelfInRelationship($jsonRelationship) === true) {
            $links[LinkInterface::SELF] = $this->getRelationshipSelfLink($model, $jsonRelationship);
        }
        if ($this->showRelatedInRelationship($jsonRelationship) === true) {
            $links[LinkInterface::RELATED] = $this->getRelationshipRelatedLink($model, $jsonRelationship);
        }

        return $links;
    }

    /**
     * Gets excludes from default 'show `self` link in relationships' rule.
     *
     * @return array Should be in ['jsonRelationship' => true] format.
     */
    protected function getExcludesFromDefaultShowSelfLinkInRelationships(): array
    {
        return [];
    }

    /**
     * Gets excludes from default 'show `related` link in relationships' rule.
     *
     * @return array Should be in ['jsonRelationship' => true] format.
     */
    protected function getExcludesFromDefaultShowRelatedLinkInRelationships(): array
    {
        return [];
    }

    /**
     * If `self` link should be shown in relationships by default.
     *
     * @return bool
     */
    protected function isShowSelfLinkInRelationships(): bool
    {
        return true;
    }

    /**
     * If `related` link should be shown in relationships by default.
     *
     * @return bool
     */
    protected function isShowRelatedLinkInRelationships(): bool
    {
        return true;
    }

    /**
     * @param mixed  $model
     * @param string $name
     *
     * @return bool
     */
    private function hasRelationship($model, string $name): bool
    {
        $hasRelationship = property_exists($model, $name);

        return $hasRelationship;
    }

    /**
     * @param string $jsonRelationship
     *
     * @return bool
     */
    private function showSelfInRelationship(string $jsonRelationship): bool
    {
        $default = $this->isShowSelfLinkInRelationships();
        $result  = isset($this->getExcludesFromDefaultShowSelfLinkInRelationships()[$jsonRelationship]) === true ?
            !$default : $default;

        return $result;
    }

    /**
     * @param string $jsonRelationship
     *
     * @return bool
     */
    private function showRelatedInRelationship(string $jsonRelationship): bool
    {
        $default = $this->isShowRelatedLinkInRelationships();
        $result  = isset($this->getExcludesFromDefaultShowRelatedLinkInRelationships()[$jsonRelationship]) === true ?
            !$default : $default;

        return $result;
    }
}
