<?php namespace Limoncello\Contracts\Model;

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
 * @package Limoncello\Contracts
 */
interface ModelSchemeInfoInterface
{
    /**
     * Check if it has information about model class.
     *
     * @param string $modelClass
     *
     * @return bool
     */
    public function hasModel(string $modelClass): bool;

    /**
     * Get model's table name.
     *
     * @param string $modelClass
     *
     * @return string
     */
    public function getTableName(string $modelClass): string;

    /**
     * Get model's primary key name.
     *
     * @param string $modelClass
     *
     * @return string
     */
    public function getPrimaryKeyName(string $modelClass): string;

    /**
     * Check if model has attribute.
     *
     * @param string $modelClass
     * @param string $attributeName
     *
     * @return bool
     */
    public function hasAttribute(string $modelClass, string $attributeName): bool;

    /**
     * Check if it has attribute length.
     *
     * @param string $modelClass
     * @param string $attributeName
     *
     * @return bool
     */
    public function hasAttributeLength(string $modelClass, string $attributeName): bool;

    /**
     * Get attribute length.
     *
     * @param string $modelClass
     * @param string $attributeName
     *
     * @return int
     */
    public function getAttributeLength(string $modelClass, string $attributeName): int;

    /**
     * Check if model has relationship.
     *
     * @param string $modelClass
     * @param string $relationshipName
     *
     * @return bool
     */
    public function hasRelationship(string $modelClass, string $relationshipName): bool;

    /**
     * Get model's relationship type.
     *
     * @param string $modelClass
     * @param string $relationshipName
     *
     * @return int
     */
    public function getRelationshipType(string $modelClass, string $relationshipName): int;

    /**
     * Get model's attribute corresponding to foreign key relationship.
     *
     * @param string $modelClass
     * @param string $relationshipName
     *
     * @return string
     */
    public function getForeignKeyAttributeName(string $modelClass, string $relationshipName): string;

    /**
     * Get model class the relationship refers to.
     *
     * @param string $modelClass
     * @param string $relationshipName
     *
     * @return string
     */
    public function getRelationshipReverseModelClass(string $modelClass, string $relationshipName): string;
}
