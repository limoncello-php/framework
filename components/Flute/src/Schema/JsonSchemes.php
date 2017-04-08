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

use Closure;
use Limoncello\Flute\Contracts\Models\ModelSchemesInterface;
use Limoncello\Flute\Contracts\Models\RelationshipStorageInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Neomerx\JsonApi\Schema\Container;

/**
 * @package Limoncello\Flute
 */
class JsonSchemes extends Container implements JsonSchemesInterface
{
    /**
     * @var RelationshipStorageInterface|null
     */
    private $relationshipStorage;

    /**
     * @var ModelSchemesInterface
     */
    private $modelSchemes;

    /**
     * @param SchemaFactoryInterface $factory
     * @param array                  $schemas
     * @param ModelSchemesInterface  $modelSchemes
     */
    public function __construct(SchemaFactoryInterface $factory, array $schemas, ModelSchemesInterface $modelSchemes)
    {
        parent::__construct($factory, $schemas);
        $this->modelSchemes = $modelSchemes;
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipStorage()
    {
        return $this->relationshipStorage;
    }

    /**
     * @inheritdoc
     */
    public function setRelationshipStorage(RelationshipStorageInterface $storage = null): JsonSchemesInterface
    {
        $this->relationshipStorage = $storage;

        return $this;
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
    public function getModelRelationshipSchema($modelClass, $relationshipName)
    {
        $reverseModelClass = $this->getModelSchemes()->getReverseModelClass($modelClass, $relationshipName);
        $targetSchema      = $this->getSchemaByType($reverseModelClass);

        return $targetSchema;
    }

    /**
     * @return ModelSchemesInterface
     */
    protected function getModelSchemes()
    {
        return $this->modelSchemes;
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @param Closure $closure
     *
     * @return SchemaInterface
     */
    protected function createSchemaFromClosure(Closure $closure)
    {
        $schema = $closure($this->getFactory(), $this, $this->getModelSchemes());

        return $schema;
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @param string $className
     *
     * @return SchemaInterface
     */
    protected function createSchemaFromClassName($className)
    {
        $schema = new $className($this->getFactory(), $this, $this->getModelSchemes());

        return $schema;
    }
}
