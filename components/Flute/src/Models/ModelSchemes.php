<?php namespace Limoncello\Flute\Models;

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
use InvalidArgumentException;
use Limoncello\Contracts\Model\RelationshipTypes;
use Limoncello\Flute\Contracts\Models\ModelSchemesInterface;

/**
 * @package Limoncello\Models
 */
class ModelSchemes implements ModelSchemesInterface
{
    /**
     * @var array
     */
    private $relationshipTypes = [];

    /**
     * @var array
     */
    private $reversedRelationships = [];

    /**
     * @var array
     */
    private $reversedClasses = [];

    /**
     * @var array
     */
    private $foreignKeys = [];

    /**
     * @var array
     */
    private $belongsToMany = [];

    /**
     * @var array
     */
    private $tableNames = [];

    /**
     * @var array
     */
    private $primaryKeys = [];

    /**
     * @var array
     */
    private $attributeTypes = [];

    /**
     * @var array
     */
    private $attributeLengths = [];

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $result = [
            $this->foreignKeys,
            $this->belongsToMany,
            $this->relationshipTypes,
            $this->reversedRelationships,
            $this->tableNames,
            $this->primaryKeys,
            $this->attributeTypes,
            $this->attributeLengths,
            $this->attributes,
            $this->reversedClasses,
        ];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function setData(array $data)
    {
        list($this->foreignKeys, $this->belongsToMany, $this->relationshipTypes,
            $this->reversedRelationships,$this->tableNames, $this->primaryKeys,
            $this->attributeTypes, $this->attributeLengths, $this->attributes, $this->reversedClasses) = $data;
    }

    /**
     * @inheritdoc
     */
    public function registerClass($class, $tableName, $primaryKey, array $attributeTypes, array $attributeLengths)
    {
        if (is_string($class) === false || empty($class) === true) {
            throw new InvalidArgumentException('class');
        }

        if (is_string($tableName) === false || empty($tableName) === true) {
            throw new InvalidArgumentException('tableName');
        }

        if (is_string($primaryKey) === false || empty($primaryKey) === true) {
            throw new InvalidArgumentException('primaryKey');
        }

        $this->tableNames[$class]       = $tableName;
        $this->primaryKeys[$class]      = $primaryKey;
        $this->attributeTypes[$class]   = $attributeTypes;
        $this->attributeLengths[$class] = $attributeLengths;
        $this->attributes[$class]       = array_keys($attributeTypes);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function hasClass($class)
    {
        $result = array_key_exists($class, $this->tableNames);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getTable($class)
    {
        $result = $this->tableNames[$class];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getPrimaryKey($class)
    {
        $result = $this->primaryKeys[$class];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getAttributeTypes($class)
    {
        $result = $this->attributeTypes[$class];

        return $result;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getAttributeTypeInstances($class)
    {
        $types  = $this->getAttributeTypes($class);
        $result = [];

        foreach ($types as $name => $type) {
            $result[$name] = Type::getType($type);
        }

        return $result;
    }


    /**
     * @inheritdoc
     */
    public function getAttributeType($class, $name)
    {
        $result = $this->attributeTypes[$class][$name];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function hasAttributeType($class, $name)
    {
        $result = isset($this->attributeTypes[$class][$name]);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLengths($class)
    {
        $result = $this->attributeLengths[$class];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function hasAttributeLength($class, $name)
    {
        $result = isset($this->attributeLengths[$class][$name]);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLength($class, $name)
    {
        $result = $this->attributeLengths[$class][$name];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes($class)
    {
        $result = $this->attributes[$class];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function hasRelationship($class, $name)
    {
        $result = isset($this->relationshipTypes[$class][$name]);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipType($class, $name)
    {
        $result = $this->relationshipTypes[$class][$name];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getReverseRelationship($class, $name)
    {
        $result = $this->reversedRelationships[$class][$name];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getReversePrimaryKey($class, $name)
    {
        $reverseClass = $this->getReverseModelClass($class, $name);

        $table = $this->getTable($reverseClass);
        $key   = $this->getPrimaryKey($reverseClass);

        return [$key, $table];
    }

    /**
     * @inheritdoc
     */
    public function getReverseForeignKey($class, $name)
    {
        list ($reverseClass, $reverseName) = $this->getReverseRelationship($class, $name);

        $table = $this->getTable($reverseClass);
        // would work only if $name is hasMany relationship
        $key   = $this->getForeignKey($reverseClass, $reverseName);

        return [$key, $table];
    }

    /**
     * @inheritdoc
     */
    public function getReverseModelClass($class, $name)
    {
        $reverseClass = $this->reversedClasses[$class][$name];

        return $reverseClass;
    }

    /**
     * @inheritdoc
     */
    public function getForeignKey($class, $name)
    {
        $result = $this->foreignKeys[$class][$name];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getBelongsToManyRelationship($class, $name)
    {
        $result = $this->belongsToMany[$class][$name];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function registerBelongsToOneRelationship($class, $name, $foreignKey, $reverseClass, $reverseName)
    {
        $this->registerRelationshipType(RelationshipTypes::BELONGS_TO, $class, $name);
        $this->registerRelationshipType(RelationshipTypes::HAS_MANY, $reverseClass, $reverseName);

        $this->registerReversedRelationship($class, $name, $reverseClass, $reverseName);
        $this->registerReversedRelationship($reverseClass, $reverseName, $class, $name);

        $this->foreignKeys[$class][$name] = $foreignKey;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function registerBelongsToManyRelationship(
        $class,
        $name,
        $table,
        $foreignKey,
        $reverseForeignKey,
        $reverseClass,
        $reverseName
    ) {
        $this->registerRelationshipType(RelationshipTypes::BELONGS_TO_MANY, $class, $name);
        $this->registerRelationshipType(RelationshipTypes::BELONGS_TO_MANY, $reverseClass, $reverseName);

        // NOTE:
        // `registerReversedRelationship` relies on duplicate registration check in `registerRelationshipType`
        // so it must be called afterwards
        $this->registerReversedRelationship($class, $name, $reverseClass, $reverseName);
        $this->registerReversedRelationship($reverseClass, $reverseName, $class, $name);

        $this->belongsToMany[$class][$name]               = [$table, $foreignKey, $reverseForeignKey];
        $this->belongsToMany[$reverseClass][$reverseName] = [$table, $reverseForeignKey, $foreignKey];

        return $this;
    }

    /**
     * @param int    $type
     * @param string $class
     * @param string $name
     */
    private function registerRelationshipType($type, $class, $name)
    {
        assert(is_int($type) === true);
        assert(is_string($class) === true && empty($class) === false);
        assert(is_string($name) === true && empty($name) === false);
        assert(
            isset($this->relationshipTypes[$class][$name]) === false,
            "Relationship `$name` for class `$class` was already used."
        );

        $this->relationshipTypes[$class][$name] = $type;
    }

    /**
     * @param string $class
     * @param string $name
     * @param string $reverseClass
     * @param string $reverseName
     */
    private function registerReversedRelationship($class, $name, $reverseClass, $reverseName)
    {
        assert(is_string($class) === true && empty($class) === false);
        assert(is_string($name) === true && empty($name) === false);
        assert(is_string($reverseClass) === true && empty($reverseClass) === false);
        assert(is_string($reverseName) === true && empty($reverseName) === false);

        // NOTE:
        // this function relies it would be called after
        // `registerRelationshipType` which prevents duplicate registrations

        $this->reversedRelationships[$class][$name] = [$reverseClass, $reverseName];
        $this->reversedClasses[$class][$name]       = $reverseClass;
    }
}
