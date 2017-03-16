<?php namespace Limoncello\Flute\Contracts\Models;

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
use Doctrine\DBAL\Types\Type;

/**
 * @package Limoncello\Models
 */
interface ModelSchemesInterface
{
    /**
     * @return array[]
     */
    public function getData();

    /**
     * @param array $data
     *
     * @return void
     */
    public function setData(array $data);

    /**
     * @param string $class
     * @param string $tableName
     * @param string $primaryKey
     * @param array  $attributeTypes
     * @param array  $attributeLengths
     *
     * @return $this
     */
    public function registerClass($class, $tableName, $primaryKey, array $attributeTypes, array $attributeLengths);

    /**
     * @param string $class
     *
     * @return bool
     */
    public function hasClass($class);

    /**
     * @param string $class
     *
     * @return string
     */
    public function getTable($class);

    /**
     * @param string $class
     *
     * @return string
     */
    public function getPrimaryKey($class);

    /**
     * @param string $class
     *
     * @return array
     */
    public function getAttributeTypes($class);

    /**
     * @param string $class
     *
     * @return Type[]
     */
    public function getAttributeTypeInstances($class);

    /**
     * @param string $class
     * @param string $name
     *
     * @return bool
     */
    public function hasAttributeType($class, $name);

    /**
     * @param string $class
     * @param string $name
     *
     * @return string
     */
    public function getAttributeType($class, $name);

    /**
     * @param string $class
     *
     * @return array
     */
    public function getAttributeLengths($class);

    /**
     * @param string $class
     *
     * @return array
     */
    public function getAttributes($class);

    /**
     * @param string $class
     * @param string $name
     *
     * @return bool
     */
    public function hasAttributeLength($class, $name);

    /**
     * @param string $class
     * @param string $name
     *
     * @return int
     */
    public function getAttributeLength($class, $name);

    /**
     * @param string $class
     * @param string $name
     *
     * @return bool
     */
    public function hasRelationship($class, $name);

    /**
     * @param string $class
     * @param string $name
     *
     * @return int
     */
    public function getRelationshipType($class, $name);

    /**
     * @param string $class
     * @param string $name
     *
     * @return array
     */
    public function getReverseRelationship($class, $name);

    /**
     * @param string $class
     * @param string $name
     *
     * @return array
     */
    public function getReversePrimaryKey($class, $name);

    /**
     * @param string $class
     * @param string $name
     *
     * @return array
     */
    public function getReverseForeignKey($class, $name);

    /**
     * @param string $class
     * @param string $name
     *
     * @return string
     */
    public function getReverseModelClass($class, $name);

    /**
     * @param string $class
     * @param string $name
     *
     * @return string
     */
    public function getForeignKey($class, $name);

    /**
     * @param string $class
     * @param string $name
     *
     * @return array
     */
    public function getBelongsToManyRelationship($class, $name);

    /**
     * @param string $class
     * @param string $name
     * @param string $foreignKey
     * @param string $reverseClass
     * @param string $reverseName
     *
     * @return $this
     */
    public function registerBelongsToOneRelationship($class, $name, $foreignKey, $reverseClass, $reverseName);

    /**
     * @param string $class
     * @param string $name
     * @param string $table
     * @param string $foreignKey
     * @param string $reverseForeignKey
     * @param string $reverseClass
     * @param string $reverseName
     *
     * @return $this
     */
    public function registerBelongsToManyRelationship(
        $class,
        $name,
        $table,
        $foreignKey,
        $reverseForeignKey,
        $reverseClass,
        $reverseName
    );
}
