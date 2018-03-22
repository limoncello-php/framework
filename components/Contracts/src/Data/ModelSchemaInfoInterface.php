<?php namespace Limoncello\Contracts\Data;

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
interface ModelSchemaInfoInterface
{
    /**
     * @param string $class
     * @param string $tableName
     * @param string $primaryKey
     * @param array  $attributeTypes
     * @param array  $attributeLengths
     *
     * @return self
     */
    public function registerClass(
        string $class,
        string $tableName,
        string $primaryKey,
        array $attributeTypes,
        array $attributeLengths
    ): self;

    /**
     * Check if it has information about model class.
     *
     * @param string $class
     *
     * @return bool
     */
    public function hasClass(string $class): bool;

    /**
     * Get model's table name.
     *
     * @param string $class
     *
     * @return string
     */
    public function getTable(string $class): string;

    /**
     * Get model's primary key name.
     *
     * @param string $class
     *
     * @return string
     */
    public function getPrimaryKey(string $class): string;

    /**
     * @param string $class
     *
     * @return array
     */
    public function getAttributeTypes(string $class): array;

    /**
     * Check if model has attribute type.
     *
     * @param string $class
     * @param string $name
     *
     * @return bool
     */
    public function hasAttributeType(string $class, string $name): bool;

    /**
     * @param string $class
     * @param string $name
     *
     * @return string
     */
    public function getAttributeType(string $class, string $name): string;

    /**
     * Check if model has attribute lengths.
     *
     * @param string $class
     *
     * @return array
     */
    public function getAttributeLengths(string $class): array;

    /**
     * @param string $class
     *
     * @return array
     */
    public function getAttributes(string $class): array;

    /**
     * Check if it has attribute length.
     *
     * @param string $class
     * @param string $name
     *
     * @return bool
     */
    public function hasAttributeLength(string $class, string $name): bool;

    /**
     * Get attribute length.
     *
     * @param string $class
     * @param string $name
     *
     * @return int
     */
    public function getAttributeLength(string $class, string $name): int;

    /**
     * Check if model has relationship.
     *
     * @param string $class
     * @param string $name
     *
     * @return bool
     */
    public function hasRelationship(string $class, string $name): bool;

    /**
     * Get model's relationship type.
     *
     * @param string $class
     * @param string $name
     *
     * @return int
     */
    public function getRelationshipType(string $class, string $name): int;

    /**
     * @param string $class
     * @param string $name
     *
     * @return array
     */
    public function getReverseRelationship(string $class, string $name): array;

    /**
     * @param string $class
     * @param string $name
     *
     * @return array
     */
    public function getReversePrimaryKey(string $class, string $name): array;

    /**
     * @param string $class
     * @param string $name
     *
     * @return array
     */
    public function getReverseForeignKey(string $class, string $name): array;

    /**
     * Get model class the relationship refers to.
     *
     * @param string $class
     * @param string $name
     *
     * @return string
     */
    public function getReverseModelClass(string $class, string $name): string;

    /**
     * Get model's attribute corresponding to foreign key relationship.
     *
     * @param string $class
     * @param string $name
     *
     * @return string
     */
    public function getForeignKey(string $class, string $name): string;

    /**
     * @param string $class
     * @param string $name
     *
     * @return array
     */
    public function getBelongsToManyRelationship(string $class, string $name): array;

    /**
     * Register `belongs to one` relationship.
     *
     * @param string $class
     * @param string $name
     * @param string $foreignKey
     * @param string $reverseClass
     * @param string $reverseName
     *
     * @return self
     */
    public function registerBelongsToOneRelationship(
        string $class,
        string $name,
        string $foreignKey,
        string $reverseClass,
        string $reverseName
    ): self;

    /** @noinspection PhpTooManyParametersInspection
     * Register `belongs to many` relationship.
     *
     * @param string $class
     * @param string $name
     * @param string $table
     * @param string $foreignKey
     * @param string $reverseForeignKey
     * @param string $reverseClass
     * @param string $reverseName
     *
     * @return self
     */
    public function registerBelongsToManyRelationship(
        string $class,
        string $name,
        string $table,
        string $foreignKey,
        string $reverseForeignKey,
        string $reverseClass,
        string $reverseName
    ): self;
}
