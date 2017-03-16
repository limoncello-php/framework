<?php namespace Limoncello\Flute\Contracts\Schema;

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

use Limoncello\Flute\Contracts\Models\RelationshipStorageInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;

/**
 * @package Limoncello\Flute
 */
interface JsonSchemesInterface extends ContainerInterface
{
    /**
     * @return RelationshipStorageInterface
     */
    public function getRelationshipStorage();

    /**
     * @param RelationshipStorageInterface $storage
     */
    public function setRelationshipStorage(RelationshipStorageInterface $storage);

    /**
     * @param string $schemaClass
     * @param string $relationshipName
     *
     * @return SchemaInterface
     */
    public function getRelationshipSchema($schemaClass, $relationshipName);

    /**
     * @param string $modelClass
     * @param string $relationshipName
     *
     * @return SchemaInterface
     */
    public function getModelRelationshipSchema($modelClass, $relationshipName);

    /**
     * @inheritdoc
     *
     * @return SchemaInterface
     */
    public function getSchema($resourceObject);

    /**
     * @inheritdoc
     *
     * @return SchemaInterface
     */
    public function getSchemaByType($type);

    /**
     * @inheritdoc
     *
     * @return SchemaInterface
     */
    public function getSchemaByResourceType($resourceType);
}
