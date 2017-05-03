<?php namespace Limoncello\Tests\Flute;

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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Flute\Contracts\Models\RelationshipStorageInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Factory;
use Limoncello\Tests\Flute\Data\Migrations\Runner as MigrationRunner;
use Limoncello\Tests\Flute\Data\Models\Board;
use Limoncello\Tests\Flute\Data\Models\Category;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Models\Emotion;
use Limoncello\Tests\Flute\Data\Models\ModelInterface;
use Limoncello\Tests\Flute\Data\Models\ModelSchemes;
use Limoncello\Tests\Flute\Data\Models\Post;
use Limoncello\Tests\Flute\Data\Models\Role;
use Limoncello\Tests\Flute\Data\Models\StringPKModel;
use Limoncello\Tests\Flute\Data\Models\User;
use Limoncello\Tests\Flute\Data\Schemes\BoardSchema;
use Limoncello\Tests\Flute\Data\Schemes\CategorySchema;
use Limoncello\Tests\Flute\Data\Schemes\CommentSchema;
use Limoncello\Tests\Flute\Data\Schemes\EmotionSchema;
use Limoncello\Tests\Flute\Data\Schemes\PostSchema;
use Limoncello\Tests\Flute\Data\Schemes\RoleSchema;
use Limoncello\Tests\Flute\Data\Schemes\UserSchema;
use Limoncello\Tests\Flute\Data\Seeds\Runner as SeedRunner;
use Limoncello\Tests\Flute\Data\Types\SystemDateTimeType;
use Mockery;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $modelClasses
     * @param bool  $requireReverseRelationships
     *
     * @return ModelSchemeInfoInterface
     */
    public static function createSchemes(
        array $modelClasses,
        $requireReverseRelationships = true
    ): ModelSchemeInfoInterface {
        $registered    = [];
        $modelSchemes  = new ModelSchemes();
        $registerModel = function ($modelClass) use ($modelSchemes, &$registered, $requireReverseRelationships) {
            /** @var ModelInterface $modelClass */
            $modelSchemes->registerClass(
                $modelClass,
                $modelClass::getTableName(),
                $modelClass::getPrimaryKeyName(),
                $modelClass::getAttributeTypes(),
                $modelClass::getAttributeLengths()
            );

            $relationships = $modelClass::getRelationships();

            if (array_key_exists(RelationshipTypes::BELONGS_TO, $relationships) === true) {
                foreach ($relationships[RelationshipTypes::BELONGS_TO] as $relName => list($rClass, $fKey, $rRel)) {
                    /** @var string $rClass */
                    $modelSchemes->registerBelongsToOneRelationship($modelClass, $relName, $fKey, $rClass, $rRel);
                    $registered[(string)$modelClass][$relName] = true;
                    $registered[$rClass][$rRel]                = true;

                    // Sanity check. Every `belongs_to` should be paired with `has_many` on the other side.
                    /** @var ModelInterface $rClass */
                    $rRelationships   = $rClass::getRelationships();
                    $isRelationshipOk = $requireReverseRelationships === false ||
                        (isset($rRelationships[RelationshipTypes::HAS_MANY][$rRel]) === true &&
                            $rRelationships[RelationshipTypes::HAS_MANY][$rRel] === [$modelClass, $fKey, $relName]);
                    /** @var string $modelClass */

                    assert($isRelationshipOk, "`belongsTo` relationship `$relName` of class $modelClass " .
                        "should be paired with `hasMany` relationship.");
                }
            }

            if (array_key_exists(RelationshipTypes::HAS_MANY, $relationships) === true) {
                foreach ($relationships[RelationshipTypes::HAS_MANY] as $relName => list($rClass, $fKey, $rRel)) {
                    // Sanity check. Every `has_many` should be paired with `belongs_to` on the other side.
                    /** @var ModelInterface $rClass */
                    $rRelationships   = $rClass::getRelationships();
                    $isRelationshipOk = $requireReverseRelationships === false ||
                        (isset($rRelationships[RelationshipTypes::BELONGS_TO][$rRel]) === true &&
                            $rRelationships[RelationshipTypes::BELONGS_TO][$rRel] === [$modelClass, $fKey, $relName]);
                    /** @var string $modelClass */
                    assert($isRelationshipOk, "`hasMany` relationship `$relName` of class $modelClass " .
                        "should be paired with `belongsTo` relationship.");
                }
            }

            if (array_key_exists(RelationshipTypes::BELONGS_TO_MANY, $relationships) === true) {
                foreach ($relationships[RelationshipTypes::BELONGS_TO_MANY] as $relName => $data) {
                    if (isset($registered[(string)$modelClass][$relName]) === true) {
                        continue;
                    }
                    /** @var string $rClass */
                    list($rClass, $iTable, $fKeyPrimary, $fKeySecondary, $rRel) = $data;
                    $modelSchemes->registerBelongsToManyRelationship(
                        $modelClass,
                        $relName,
                        $iTable,
                        $fKeyPrimary,
                        $fKeySecondary,
                        $rClass,
                        $rRel
                    );
                    $registered[(string)$modelClass][$relName] = true;
                    $registered[$rClass][$rRel]                = true;
                }
            }
        };

        array_map($registerModel, $modelClasses);

        return $modelSchemes;
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        if (Type::hasType(SystemDateTimeType::NAME) === false) {
            Type::addType(SystemDateTimeType::NAME, SystemDateTimeType::class);
        }
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @return Connection
     */
    protected function createConnection()
    {
        $connection = DriverManager::getConnection(['url' => 'sqlite:///', 'memory' => true]);
        $this->assertNotSame(false, $connection->exec('PRAGMA foreign_keys = ON;'));

        return $connection;
    }

    /**
     * @param Connection $connection
     */
    protected function migrateDatabase(Connection $connection)
    {
        (new MigrationRunner())->migrate($connection->getSchemaManager());
        (new SeedRunner())->run($connection);
    }

    /**
     * @return Connection
     */
    protected function initDb()
    {
        $connection = $this->createConnection();
        $this->migrateDatabase($connection);

        return $connection;
    }

    /**
     * @return ModelSchemeInfoInterface
     */
    protected function getModelSchemes()
    {
        $modelSchemes = static::createSchemes([
            Board::class,
            Comment::class,
            Emotion::class,
            Post::class,
            Role::class,
            User::class,
            Category::class,
            StringPKModel::class,
        ]);

        return $modelSchemes;
    }

    /**
     * @param Factory                           $factory
     * @param ModelSchemeInfoInterface          $modelSchemes
     * @param RelationshipStorageInterface|null $storage
     *
     * @return JsonSchemesInterface
     */
    protected function getJsonSchemes(
        Factory $factory,
        ModelSchemeInfoInterface $modelSchemes,
        RelationshipStorageInterface $storage = null
    ) {
        $schemes = $factory->createJsonSchemes($this->getSchemeMap(), $modelSchemes);
        $storage === null ?: $schemes->setRelationshipStorage($storage);

        return $schemes;
    }

    /**
     * @return array
     */
    protected function getSchemeMap()
    {
        return [
            Board::class   => function (
                FactoryInterface $factory,
                JsonSchemesInterface $container,
                ModelSchemeInfoInterface $modelSchemes
            ) {
                return new BoardSchema($factory, $container, $modelSchemes);
            },
            Comment::class  => CommentSchema::class,
            Emotion::class  => EmotionSchema::class,
            Post::class     => PostSchema::class,
            Role::class     => RoleSchema::class,
            User::class     => UserSchema::class,
            Category::class => CategorySchema::class,
        ];
    }
}
