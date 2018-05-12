<?php namespace Limoncello\Tests\Application\Data;

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

use Doctrine\DBAL\Types\Type;
use Limoncello\Application\Data\ModelSchemaInfo;
use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Tests\Application\Data\Models\Comment;
use Limoncello\Tests\Application\Data\Models\CommentEmotion;
use Limoncello\Tests\Application\Data\Models\Emotion;
use Limoncello\Tests\Application\Data\Models\User;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Application
 */
class ModelSchemasTest extends TestCase
{
    /**
     * @var ModelSchemaInfoInterface
     */
    private $schemas;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->schemas = new ModelSchemaInfo();

        $this->setUpStorage();

        // before every test get and set internal data to make sure
        // functionality works fine when restored from cache
        $cache = $this->schemas->getData();
        $this->schemas->setData($cache);
    }

    /**
     * Test register class.
     */
    public function testRegisterClass(): void
    {
        $this->assertEquals(Comment::class, Comment::class);
        $this->assertTrue($this->schemas->hasClass(Comment::class));
        $this->assertFalse($this->schemas->hasClass(get_class($this)));
        $this->assertEquals(Comment::TABLE_NAME, $this->schemas->getTable(Comment::class));
        $this->assertEquals(Comment::FIELD_ID, $this->schemas->getPrimaryKey(Comment::class));
        $this->assertNotEmpty($this->schemas->getAttributeTypes(Comment::class));
        $this->assertNotEmpty($this->schemas->getAttributeLengths(Comment::class));
        $this->assertEquals([
            Comment::FIELD_ID,
            Comment::FIELD_ID_USER,
            Comment::FIELD_TEXT,
            Comment::FIELD_CREATED_AT,
        ], $this->schemas->getAttributes(Comment::class));
        $this->assertTrue($this->schemas->hasAttributeType(Comment::class, Comment::FIELD_TEXT));
        $this->assertFalse($this->schemas->hasAttributeType(Comment::class, 'non-existing-field'));
        $this->assertTrue($this->schemas->hasAttributeLength(Comment::class, Comment::FIELD_TEXT));
        $this->assertFalse($this->schemas->hasAttributeLength(Comment::class, Comment::FIELD_CREATED_AT));
        $this->assertEquals(Type::STRING, $this->schemas->getAttributeType(Comment::class, Comment::FIELD_TEXT));
        $this->assertEquals(
            Type::DATE,
            $this->schemas->getAttributeType(Comment::class, Comment::FIELD_CREATED_AT)
        );
        $this->assertEquals(
            Comment::LENGTH_TEXT,
            $this->schemas->getAttributeLength(Comment::class, Comment::FIELD_TEXT)
        );
    }

    /**
     * Test register to 1 relationship.
     */
    public function testRegisterToOneRelationship(): void
    {
        $this->assertTrue($this->schemas->hasRelationship(Comment::class, Comment::REL_USER));
        $this->assertEquals(
            RelationshipTypes::BELONGS_TO,
            $this->schemas->getRelationshipType(Comment::class, Comment::REL_USER)
        );
        $this->assertEquals(
            RelationshipTypes::HAS_MANY,
            $this->schemas->getRelationshipType(User::class, User::REL_COMMENTS)
        );
        $this->assertEquals(Comment::FIELD_ID_USER, $this->schemas->getForeignKey(Comment::class, Comment::REL_USER));
        $this->assertEquals(
            [User::class, User::REL_COMMENTS],
            $this->schemas->getReverseRelationship(Comment::class, Comment::REL_USER)
        );
        $this->assertEquals(
            [Comment::class, Comment::REL_USER],
            $this->schemas->getReverseRelationship(User::class, User::REL_COMMENTS)
        );
        $this->assertEquals(
            [Comment::FIELD_ID, Comment::TABLE_NAME],
            $this->schemas->getReversePrimaryKey(User::class, User::REL_COMMENTS)
        );
        $this->assertEquals(
            [Comment::FIELD_ID_USER, Comment::TABLE_NAME],
            $this->schemas->getReverseForeignKey(User::class, User::REL_COMMENTS)
        );
        $this->assertEquals(
            Comment::class,
            $this->schemas->getReverseModelClass(User::class, User::REL_COMMENTS)
        );
    }

    /**
     * Test register to many relationship.
     */
    public function testRegisterToManyRelationship(): void
    {
        $this->assertTrue($this->schemas->hasRelationship(Comment::class, Comment::REL_EMOTIONS));
        $this->assertEquals(
            RelationshipTypes::BELONGS_TO_MANY,
            $this->schemas->getRelationshipType(Comment::class, Comment::REL_EMOTIONS)
        );
        $this->assertEquals(
            RelationshipTypes::BELONGS_TO_MANY,
            $this->schemas->getRelationshipType(Emotion::class, Emotion::REL_COMMENTS)
        );
        $this->assertEquals([
            CommentEmotion::TABLE_NAME,
            CommentEmotion::FIELD_ID_COMMENT,
            CommentEmotion::FIELD_ID_EMOTION,
        ], $this->schemas->getBelongsToManyRelationship(Comment::class, Comment::REL_EMOTIONS));
        $this->assertEquals([
            CommentEmotion::TABLE_NAME,
            CommentEmotion::FIELD_ID_EMOTION,
            CommentEmotion::FIELD_ID_COMMENT,
        ], $this->schemas->getBelongsToManyRelationship(Emotion::class, Emotion::REL_COMMENTS));
        $this->assertEquals(
            [Emotion::class, Emotion::REL_COMMENTS],
            $this->schemas->getReverseRelationship(Comment::class, Comment::REL_EMOTIONS)
        );
        $this->assertEquals(
            [Comment::class, Comment::REL_EMOTIONS],
            $this->schemas->getReverseRelationship(Emotion::class, Emotion::REL_COMMENTS)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCannotRegisterWithEmptyClass(): void
    {
        $this->schemas->registerClass(
            '',
            Comment::TABLE_NAME,
            Comment::FIELD_ID,
            [
                Comment::FIELD_ID         => Type::INTEGER,
                Comment::FIELD_ID_USER    => Type::INTEGER,
                Comment::FIELD_TEXT       => Type::STRING,
                Comment::FIELD_CREATED_AT => Type::DATE
            ],
            [Comment::FIELD_TEXT => Comment::LENGTH_TEXT]
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCannotRegisterWithEmptyTableName(): void
    {
        $this->schemas->registerClass(
            Comment::class,
            '',
            Comment::FIELD_ID,
            [
                Comment::FIELD_ID         => Type::INTEGER,
                Comment::FIELD_ID_USER    => Type::INTEGER,
                Comment::FIELD_TEXT       => Type::STRING,
                Comment::FIELD_CREATED_AT => Type::DATE
            ],
            [Comment::FIELD_TEXT => Comment::LENGTH_TEXT]
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCannotRegisterWithEmptyPrimaryKey(): void
    {
        $this->schemas->registerClass(
            Comment::class,
            Comment::TABLE_NAME,
            '',
            [
                Comment::FIELD_ID         => Type::INTEGER,
                Comment::FIELD_ID_USER    => Type::INTEGER,
                Comment::FIELD_TEXT       => Type::STRING,
                Comment::FIELD_CREATED_AT => Type::DATE
            ],
            [Comment::FIELD_TEXT => Comment::LENGTH_TEXT]
        );
    }

    /**
     * @return void
     */
    private function setUpStorage(): void
    {
        $this->schemas->registerClass(
            Comment::class,
            Comment::TABLE_NAME,
            Comment::FIELD_ID,
            [
                Comment::FIELD_ID         => Type::INTEGER,
                Comment::FIELD_ID_USER    => Type::INTEGER,
                Comment::FIELD_TEXT       => Type::STRING,
                Comment::FIELD_CREATED_AT => Type::DATE
            ],
            [Comment::FIELD_TEXT => Comment::LENGTH_TEXT]
        );

        $this->registerTo1();
        $this->registerToMany();
    }

    private function registerTo1(): void
    {
        $this->schemas->registerBelongsToOneRelationship(
            Comment::class,
            Comment::REL_USER,
            Comment::FIELD_ID_USER,
            User::class,
            User::REL_COMMENTS
        );
    }

    private function registerToMany(): void
    {
        $this->schemas->registerBelongsToManyRelationship(
            Comment::class,
            Comment::REL_EMOTIONS,
            CommentEmotion::TABLE_NAME,
            CommentEmotion::FIELD_ID_COMMENT,
            CommentEmotion::FIELD_ID_EMOTION,
            Emotion::class,
            Emotion::REL_COMMENTS
        );
    }
}
