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

use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Flute\Exceptions\InvalidSchemeFactoryException;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Neomerx\JsonApi\Schema\Container;

/**
 * @package Limoncello\Flute
 */
class JsonSchemes extends Container implements JsonSchemesInterface
{
    /**
     * @var ModelSchemeInfoInterface
     */
    private $modelSchemes;

    /**
     * @param SchemaFactoryInterface   $factory
     * @param array                    $schemas
     * @param ModelSchemeInfoInterface $modelSchemes
     */
    public function __construct(SchemaFactoryInterface $factory, array $schemas, ModelSchemeInfoInterface $modelSchemes)
    {
        parent::__construct($factory, $schemas);
        $this->modelSchemes = $modelSchemes;
    }

    /**
     * @inheritdoc
     */
    public function hasRelationshipSchema(string $schemaClass, string $relationshipName): bool
    {
        assert(
            class_exists($schemaClass) === true &&
            in_array(SchemaInterface::class, class_implements($schemaClass)) === true
        );

        /** @var SchemaInterface $schemaClass */

        $hasRel = $schemaClass::getMappings()[SchemaInterface::SCHEMA_RELATIONSHIPS][$relationshipName] ?? false;

        assert($hasRel === false || $this->getRelationshipSchema($schemaClass, $relationshipName) !== null);

        return $hasRel !== false;
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipSchema(string $schemaClass, string $relationshipName): SchemaInterface
    {
        assert(
            class_exists($schemaClass) === true &&
            in_array(SchemaInterface::class, class_implements($schemaClass)) === true
        );

        /** @var SchemaInterface $schemaClass */

        $modelRelName = $schemaClass::getMappings()[SchemaInterface::SCHEMA_RELATIONSHIPS][$relationshipName];
        $targetSchema = $this->getModelRelationshipSchema($schemaClass::MODEL, $modelRelName);

        return $targetSchema;
    }

    /**
     * @inheritdoc
     */
    public function getModelRelationshipSchema(string $modelClass, string $relationshipName): SchemaInterface
    {
        $reverseModelClass = $this->getModelSchemes()->getReverseModelClass($modelClass, $relationshipName);

        /** @var SchemaInterface $targetSchema */
        $targetSchema = $this->getSchemaByType($reverseModelClass);

        return $targetSchema;
    }

    /**
     * @return ModelSchemeInfoInterface
     */
    protected function getModelSchemes(): ModelSchemeInfoInterface
    {
        return $this->modelSchemes;
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @param callable $callable
     *
     * @return SchemaInterface
     *
     * @codeCoverageIgnore
     */
    protected function createSchemaFromCallable(callable $callable)
    {
        assert($callable);

        // callable as Scheme factory is not supported.
        throw new InvalidSchemeFactoryException();
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @param string $className
     *
     * @return SchemaInterface
     */
    protected function createSchemaFromClassName($className)
    {
        $schema = new $className($this->getFactory(), $this->getModelSchemes());

        return $schema;
    }
}
