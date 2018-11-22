<?php namespace Limoncello\Tests\Flute\Data\Models;

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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;

/**
 * @package Limoncello\Flute
 */
class ModelSchemas implements ModelSchemaInfoInterface
{
    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @throws DBALException
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

    // Code below copy-pasted from Application component

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
     * @var array
     */
    private $rawAttributes = [];

    /**
     * @inheritdoc
     */
    public function getData(): array
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
            $this->rawAttributes,
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
            $this->attributeTypes, $this->attributeLengths, $this->attributes, $this->rawAttributes,
            $this->reversedClasses) = $data;
    }

    /**
     * @inheritdoc
     */
    public function registerClass(
        string $class,
        string $tableName,
        string $primaryKey,
        array $attributeTypes,
        array $attributeLengths,
        array $rawAttributes = []
    ): ModelSchemaInfoInterface {
        if (empty($class) === true) {
            throw new InvalidArgumentException('class');
        }

        if (empty($tableName) === true) {
            throw new InvalidArgumentException('tableName');
        }

        if (empty($primaryKey) === true) {
            throw new InvalidArgumentException('primaryKey');
        }

        $this->tableNames[$class]       = $tableName;
        $this->primaryKeys[$class]      = $primaryKey;
        $this->attributeTypes[$class]   = $attributeTypes;
        $this->attributeLengths[$class] = $attributeLengths;
        $this->attributes[$class]       = array_keys($attributeTypes);
        $this->rawAttributes[$class]    = $rawAttributes;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function hasClass(string $class): bool
    {
        $result = array_key_exists($class, $this->tableNames);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getTable(string $class): string
    {
        $result = $this->tableNames[$class];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getPrimaryKey(string $class): string
    {
        $result = $this->primaryKeys[$class];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getAttributeTypes(string $class): array
    {
        $result = $this->attributeTypes[$class];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getAttributeType(string $class, string $name): string
    {
        $result = $this->attributeTypes[$class][$name];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function hasAttributeType(string $class, string $name): bool
    {
        $result = isset($this->attributeTypes[$class][$name]);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLengths(string $class): array
    {
        $result = $this->attributeLengths[$class];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function hasAttributeLength(string $class, string $name): bool
    {
        $result = isset($this->attributeLengths[$class][$name]);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLength(string $class, string $name): int
    {
        $result = $this->attributeLengths[$class][$name];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes(string $class): array
    {
        $result = $this->attributes[$class];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getRawAttributes(string $class): array
    {
        $result = $this->rawAttributes[$class];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function hasRelationship(string $class, string $name): bool
    {
        $result = isset($this->relationshipTypes[$class][$name]);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipType(string $class, string $name): int
    {
        $result = $this->relationshipTypes[$class][$name];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getReverseRelationship(string $class, string $name): array
    {
        $result = $this->reversedRelationships[$class][$name];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getReversePrimaryKey(string $class, string $name): array
    {
        $reverseClass = $this->getReverseModelClass($class, $name);

        $table = $this->getTable($reverseClass);
        $key   = $this->getPrimaryKey($reverseClass);

        return [$key, $table];
    }

    /**
     * @inheritdoc
     */
    public function getReverseForeignKey(string $class, string $name): array
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
    public function getReverseModelClass(string $class, string $name): string
    {
        $reverseClass = $this->reversedClasses[$class][$name];

        return $reverseClass;
    }

    /**
     * @inheritdoc
     */
    public function getForeignKey(string $class, string $name): string
    {
        $result = $this->foreignKeys[$class][$name];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getBelongsToManyRelationship(string $class, string $name): array
    {
        $result = $this->belongsToMany[$class][$name];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function registerBelongsToOneRelationship(
        string $class,
        string $name,
        string $foreignKey,
        string $reverseClass,
        string $reverseName
    ): ModelSchemaInfoInterface {
        $this->registerRelationshipType(RelationshipTypes::BELONGS_TO, $class, $name);
        $this->registerRelationshipType(RelationshipTypes::HAS_MANY, $reverseClass, $reverseName);

        $this->registerReversedRelationship($class, $name, $reverseClass, $reverseName);
        $this->registerReversedRelationship($reverseClass, $reverseName, $class, $name);

        $this->foreignKeys[$class][$name] = $foreignKey;

        return $this;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @inheritdoc
     */
    public function registerBelongsToManyRelationship(
        string $class,
        string $name,
        string $table,
        string $foreignKey,
        string $reverseForeignKey,
        string $reverseClass,
        string $reverseName
    ): ModelSchemaInfoInterface {
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
     *
     * @return void
     */
    private function registerRelationshipType(int $type, string $class, string $name)
    {
        assert(empty($class) === false && empty($name) === false);
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
     *
     * @return void
     */
    private function registerReversedRelationship(
        string $class,
        string $name,
        string $reverseClass,
        string $reverseName
    ) {
        assert(
            empty($class) === false &&
            empty($name) === false &&
            empty($reverseClass) === false &&
            empty($reverseName) === false
        );

        // NOTE:
        // this function relies it would be called after
        // `registerRelationshipType` which prevents duplicate registrations

        $this->reversedRelationships[$class][$name] = [$reverseClass, $reverseName];
        $this->reversedClasses[$class][$name]       = $reverseClass;
    }
}
