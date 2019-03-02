<?php declare (strict_types = 1);

namespace Limoncello\Flute\Contracts\Schema;

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

use Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface;

/**
 * @package Limoncello\Flute
 */
interface JsonSchemasInterface extends SchemaContainerInterface
{
    /**
     * @param string $schemaClass
     * @param string $relationshipName
     *
     * @return bool
     */
    public function hasRelationshipSchema(string $schemaClass, string $relationshipName): bool;

    /**
     * @param string $schemaClass
     * @param string $relationshipName
     *
     * @return SchemaInterface
     */
    public function getRelationshipSchema(string $schemaClass, string $relationshipName): SchemaInterface;

    /**
     * @param string $modelClass
     * @param string $relationshipName
     *
     * @return SchemaInterface
     */
    public function getModelRelationshipSchema(string $modelClass, string $relationshipName): SchemaInterface;

    /**
     * @param string $resourceType
     *
     * @return SchemaInterface
     */
    public function getSchemaByResourceType(string $resourceType): SchemaInterface;
}
