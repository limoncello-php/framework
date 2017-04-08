<?php namespace Limoncello\Tests\Flute\Models;

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
use Limoncello\Contracts\Model\RelationshipTypes;
use Limoncello\Flute\Contracts\Models\ModelSchemesInterface;
use Limoncello\Flute\Models\ModelSchemes;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Models\CommentEmotion;
use Limoncello\Tests\Flute\Data\Models\Emotion;
use Limoncello\Tests\Flute\Data\Models\User;

/**
 * @package Limoncello\Tests\Flute
 */
class ModelSchemesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ModelSchemesInterface
     */
    private $schemes;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->schemes = new ModelSchemes();

        $this->setUpStorage();

        // before every test get and set internal data to make sure
        // functionality works fine when restored from cache
        $cache = $this->schemes->getData();
        $this->schemes->setData($cache);
    }

    /**
     * Test register class.
     */
    public function testRegisterClass()
    {
        $this->assertEquals(Comment::class, Comment::class);
        $this->assertTrue($this->schemes->hasClass(Comment::class));
        $this->assertFalse($this->schemes->hasClass(get_class($this)));
        $this->assertEquals(Comment::TABLE_NAME, $this->schemes->getTable(Comment::class));
        $this->assertEquals(Comment::FIELD_ID, $this->schemes->getPrimaryKey(Comment::class));
        $this->assertNotEmpty($this->schemes->getAttributeTypes(Comment::class));
        $this->assertNotEmpty($this->schemes->getAttributeLengths(Comment::class));
        $this->assertEquals([
            Comment::FIELD_ID,
            Comment::FIELD_ID_USER,
            Comment::FIELD_TEXT,
            Comment::FIELD_CREATED_AT,
        ], $this->schemes->getAttributes(Comment::class));
        $this->assertTrue($this->schemes->hasAttributeType(Comment::class, Comment::FIELD_TEXT));
        $this->assertFalse($this->schemes->hasAttributeType(Comment::class, 'non-existing-field'));
        $this->assertTrue($this->schemes->hasAttributeLength(Comment::class, Comment::FIELD_TEXT));
        $this->assertFalse($this->schemes->hasAttributeLength(Comment::class, Comment::FIELD_CREATED_AT));
        $this->assertEquals(Type::STRING, $this->schemes->getAttributeType(Comment::class, Comment::FIELD_TEXT));
        $this->assertEquals(
            Type::DATE,
            $this->schemes->getAttributeType(Comment::class, Comment::FIELD_CREATED_AT)
        );
        $this->assertEquals(
            Comment::LENGTH_TEXT,
            $this->schemes->getAttributeLength(Comment::class, Comment::FIELD_TEXT)
        );
    }

    /**
     * Test register to 1 relationship.
     */
    public function testRegisterToOneRelationship()
    {
        $this->assertTrue($this->schemes->hasRelationship(Comment::class, Comment::REL_USER));
        $this->assertEquals(
            RelationshipTypes::BELONGS_TO,
            $this->schemes->getRelationshipType(Comment::class, Comment::REL_USER)
        );
        $this->assertEquals(
            RelationshipTypes::HAS_MANY,
            $this->schemes->getRelationshipType(User::class, User::REL_COMMENTS)
        );
        $this->assertEquals(Comment::FIELD_ID_USER, $this->schemes->getForeignKey(Comment::class, Comment::REL_USER));
        $this->assertEquals(
            [User::class, User::REL_COMMENTS],
            $this->schemes->getReverseRelationship(Comment::class, Comment::REL_USER)
        );
        $this->assertEquals(
            [Comment::class, Comment::REL_USER],
            $this->schemes->getReverseRelationship(User::class, User::REL_COMMENTS)
        );
        $this->assertEquals(
            [Comment::FIELD_ID, Comment::TABLE_NAME],
            $this->schemes->getReversePrimaryKey(User::class, User::REL_COMMENTS)
        );
        $this->assertEquals(
            [Comment::FIELD_ID_USER, Comment::TABLE_NAME],
            $this->schemes->getReverseForeignKey(User::class, User::REL_COMMENTS)
        );
        $this->assertEquals(
            Comment::class,
            $this->schemes->getReverseModelClass(User::class, User::REL_COMMENTS)
        );
    }

    /**
     * Test register to many relationship.
     */
    public function testRegisterToManyRelationship()
    {
        $this->assertTrue($this->schemes->hasRelationship(Comment::class, Comment::REL_EMOTIONS));
        $this->assertEquals(
            RelationshipTypes::BELONGS_TO_MANY,
            $this->schemes->getRelationshipType(Comment::class, Comment::REL_EMOTIONS)
        );
        $this->assertEquals(
            RelationshipTypes::BELONGS_TO_MANY,
            $this->schemes->getRelationshipType(Emotion::class, Emotion::REL_COMMENTS)
        );
        $this->assertEquals([
            CommentEmotion::TABLE_NAME,
            CommentEmotion::FIELD_ID_COMMENT,
            CommentEmotion::FIELD_ID_EMOTION,
        ], $this->schemes->getBelongsToManyRelationship(Comment::class, Comment::REL_EMOTIONS));
        $this->assertEquals([
            CommentEmotion::TABLE_NAME,
            CommentEmotion::FIELD_ID_EMOTION,
            CommentEmotion::FIELD_ID_COMMENT,
        ], $this->schemes->getBelongsToManyRelationship(Emotion::class, Emotion::REL_COMMENTS));
        $this->assertEquals(
            [Emotion::class, Emotion::REL_COMMENTS],
            $this->schemes->getReverseRelationship(Comment::class, Comment::REL_EMOTIONS)
        );
        $this->assertEquals(
            [Comment::class, Comment::REL_EMOTIONS],
            $this->schemes->getReverseRelationship(Emotion::class, Emotion::REL_COMMENTS)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCannotRegisterWithEmptyClass()
    {
        $this->schemes->registerClass(
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
    public function testCannotRegisterWithEmptyTableName()
    {
        $this->schemes->registerClass(
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
    public function testCannotRegisterWithEmptyPrimaryKey()
    {
        $this->schemes->registerClass(
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
    private function setUpStorage()
    {
        $this->schemes->registerClass(
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

    private function registerTo1()
    {
        $this->schemes->registerBelongsToOneRelationship(
            Comment::class,
            Comment::REL_USER,
            Comment::FIELD_ID_USER,
            User::class,
            User::REL_COMMENTS
        );
    }

    private function registerToMany()
    {
        $this->schemes->registerBelongsToManyRelationship(
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
