<?php namespace Limoncello\Tests\Flute\Adapters;

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

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Exception;
use Limoncello\Flute\Adapters\ModelQueryBuilder;
use Limoncello\Flute\Contracts\Http\Query\FilterParameterInterface;
use Limoncello\Tests\Flute\Data\Models\Board;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Models\Emotion;
use Limoncello\Tests\Flute\Data\Models\Post;
use Limoncello\Tests\Flute\Data\Models\User;
use Limoncello\Tests\Flute\TestCase;

/**
 * @package Limoncello\Tests\Flute
 */
class ModelQueryBuilderTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->connection = $this->createConnection();
    }

    /**
     * Test filtering in BelongsTo relationship.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testAddBelongsToRelationshipAndFilterWithSort(): void
    {
        $builder = $this->createModelQueryBuilder(Post::class);

        $filters = [
            User::FIELD_ID => [
                FilterParameterInterface::OPERATION_EQUALS => [1],
            ],
        ];
        $sorts   = [
            User::FIELD_FIRST_NAME => false,
        ];
        $builder
            ->selectModelColumns()
            ->fromModelTable()
            ->addRelationshipFiltersAndSortsWithAnd(Post::REL_USER, $filters, $sorts);

        $expected =
            'SELECT `posts1`.`id_post`, `posts1`.`id_board_fk`, `posts1`.`id_user_fk`, `posts1`.`id_editor_fk`, ' .
            '`posts1`.`title`, `posts1`.`text`, `posts1`.`created_at`, `posts1`.`updated_at`, `posts1`.`deleted_at` ' .
            'FROM `posts` `posts1` ' .
            'INNER JOIN `users` `users2` ON `posts1`.`id_user_fk`=`users2`.`id_user` ' .
            'WHERE `users2`.`id_user` = :dcValue1 ' .
            'ORDER BY `users2`.`first_name` DESC';
        $this->assertEquals($expected, $builder->getSQL());

        $this->migrateDatabase($this->connection);
        $this->assertNotEmpty($posts = $builder->execute()->fetchAll());
        $this->assertCount(4, $posts);
    }

    /**
     * Test filtering in BelongsTo relationship.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testAddBelongsToRelationshipFilterOnly(): void
    {
        $builder = $this->createModelQueryBuilder(Post::class);

        $filters = [
            User::FIELD_ID => [
                FilterParameterInterface::OPERATION_EQUALS => [1],
            ],
        ];
        $sorts   = null;
        $builder
            ->selectModelColumns()
            ->fromModelTable()
            ->addRelationshipFiltersAndSortsWithAnd(Post::REL_USER, $filters, $sorts);

        $expected =
            'SELECT `posts1`.`id_post`, `posts1`.`id_board_fk`, `posts1`.`id_user_fk`, `posts1`.`id_editor_fk`, ' .
            '`posts1`.`title`, `posts1`.`text`, `posts1`.`created_at`, `posts1`.`updated_at`, `posts1`.`deleted_at` ' .
            'FROM `posts` `posts1` ' .
            'INNER JOIN `users` `users2` ON `posts1`.`id_user_fk`=`users2`.`id_user` ' .
            'WHERE `users2`.`id_user` = :dcValue1';
        $this->assertEquals($expected, $builder->getSQL());

        $this->migrateDatabase($this->connection);
        $this->assertNotEmpty($posts = $builder->execute()->fetchAll());
        $this->assertCount(4, $posts);
    }

    /**
     * Test sorting in BelongsTo relationship.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testAddBelongsToRelationshipSortOnly(): void
    {
        $builder = $this->createModelQueryBuilder(Post::class);

        $filters = [
        ];
        $sorts   = [
            User::FIELD_FIRST_NAME => false,
        ];
        $builder
            ->selectModelColumns()
            ->fromModelTable()
            ->addRelationshipFiltersAndSortsWithAnd(Post::REL_USER, $filters, $sorts);

        $expected =
            'SELECT `posts1`.`id_post`, `posts1`.`id_board_fk`, `posts1`.`id_user_fk`, `posts1`.`id_editor_fk`, ' .
            '`posts1`.`title`, `posts1`.`text`, `posts1`.`created_at`, `posts1`.`updated_at`, `posts1`.`deleted_at` ' .
            'FROM `posts` `posts1` ' .
            'INNER JOIN `users` `users2` ON `posts1`.`id_user_fk`=`users2`.`id_user` ' .
            'ORDER BY `users2`.`first_name` DESC';
        $this->assertEquals($expected, $builder->getSQL());

        $this->migrateDatabase($this->connection);
        $this->assertNotEmpty($posts = $builder->execute()->fetchAll());
        $this->assertCount(20, $posts);
    }

    /**
     * Test filtering in HasMany relationship.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testAddHasManyRelationshipAndFilter(): void
    {
        $builder = $this->createModelQueryBuilder(Post::class);
        $filters = [
            Comment::FIELD_ID => [
                FilterParameterInterface::OPERATION_GREATER_OR_EQUALS => [1],
                FilterParameterInterface::OPERATION_LESS_OR_EQUALS    => [2],
            ],
        ];
        $sorts   = [
            Comment::FIELD_TEXT => true,
        ];

        // select all posts which has comments ID between 1 and 2.
        $builder
            ->selectModelColumns()
            ->fromModelTable()
            ->addRelationshipFiltersAndSortsWithAnd(Post::REL_COMMENTS, $filters, $sorts);

        $expected =
            'SELECT `posts1`.`id_post`, `posts1`.`id_board_fk`, `posts1`.`id_user_fk`, `posts1`.`id_editor_fk`, ' .
            '`posts1`.`title`, `posts1`.`text`, `posts1`.`created_at`, `posts1`.`updated_at`, `posts1`.`deleted_at` ' .
            'FROM `posts` `posts1` ' .
            'INNER JOIN `comments` `comments2` ON `posts1`.`id_post`=`comments2`.`id_post_fk` ' .
            'WHERE (`comments2`.`id_comment` >= :dcValue1) AND (`comments2`.`id_comment` <= :dcValue2) ' .
            'ORDER BY `comments2`.`text` ASC';
        $this->assertEquals($expected, $builder->getSQL());

        $this->migrateDatabase($this->connection);
        $this->assertNotEmpty($posts = $builder->execute()->fetchAll());
        $this->assertCount(2, $posts);
    }

    /**
     * Test filtering in HasMany relationship.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testAddHasManyRelationshipOrFilter(): void
    {
        $builder = $this->createModelQueryBuilder(Post::class);
        $filters = [
            Comment::FIELD_ID => [
                FilterParameterInterface::OPERATION_GREATER_OR_EQUALS => [1],
                FilterParameterInterface::OPERATION_LESS_OR_EQUALS    => [2],
            ],
        ];
        $sorts   = [
            Comment::FIELD_TEXT => true,
        ];

        // select all posts which has comments ID between 1 and 2.
        $builder
            ->selectModelColumns()
            ->fromModelTable()
            ->addRelationshipFiltersAndSortsWithOr(Post::REL_COMMENTS, $filters, $sorts);

        $expected =
            'SELECT `posts1`.`id_post`, `posts1`.`id_board_fk`, `posts1`.`id_user_fk`, `posts1`.`id_editor_fk`, ' .
            '`posts1`.`title`, `posts1`.`text`, `posts1`.`created_at`, `posts1`.`updated_at`, `posts1`.`deleted_at` ' .
            'FROM `posts` `posts1` ' .
            'INNER JOIN `comments` `comments2` ON `posts1`.`id_post`=`comments2`.`id_post_fk` ' .
            'WHERE (`comments2`.`id_comment` >= :dcValue1) OR (`comments2`.`id_comment` <= :dcValue2) ' .
            'ORDER BY `comments2`.`text` ASC';
        $this->assertEquals($expected, $builder->getSQL());

        $this->migrateDatabase($this->connection);
        $this->assertNotEmpty($posts = $builder->execute()->fetchAll());
        $this->assertCount(100, $posts);
    }

    /**
     * Test filtering in BelongsTo relationship.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testAddBelongsToManyRelationshipFilter(): void
    {
        $builder = $this->createModelQueryBuilder(Comment::class);
        $filters = [
            Emotion::FIELD_ID => [
                FilterParameterInterface::OPERATION_EQUALS => [1],
            ],
        ];
        $sorts   = [
            Emotion::FIELD_NAME => true,
            Emotion::FIELD_ID   => false,
        ];

        // select all comments with emotion ID=1
        $builder
            ->selectModelColumns()
            ->fromModelTable()
            ->addRelationshipFiltersAndSortsWithAnd(Comment::REL_EMOTIONS, $filters, $sorts);

        $expected =
            'SELECT `comments1`.`id_comment`, `comments1`.`id_post_fk`, `comments1`.`id_user_fk`, ' .
            '`comments1`.`text`, `comments1`.`int_value`, `comments1`.`float_value`, `comments1`.`bool_value`, ' .
            '`comments1`.`datetime_value`, `comments1`.`created_at`, `comments1`.`updated_at`, ' .
            '`comments1`.`deleted_at` ' .
            'FROM `comments` `comments1` ' .
            'INNER JOIN `comments_emotions` `comments_emotions2` ON ' .
            '`comments1`.`id_comment`=`comments_emotions2`.`id_comment_fk` ' .
            'INNER JOIN `emotions` `emotions3` ON `comments_emotions2`.`id_emotion_fk`=`emotions3`.`id_emotion` ' .
            'WHERE `emotions3`.`id_emotion` = :dcValue1 ' .
            'ORDER BY `emotions3`.`name` ASC, `emotions3`.`id_emotion` DESC';
        $this->assertEquals($expected, $builder->getSQL());

        $this->migrateDatabase($this->connection);
        $this->assertNotEmpty($comments = $builder->execute()->fetchAll());
        $this->assertCount(35, $comments);
    }

    /**
     * Test builder.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testRead(): void
    {
        $builder = $this->createModelQueryBuilder(Board::class);
        $filters = [
            Board::FIELD_CREATED_AT => [
                FilterParameterInterface::OPERATION_EQUALS => [new DateTime()],
            ],
        ];
        $sorts   = [
            Board::FIELD_TITLE => true,
        ];

        $builder
            ->selectModelColumns()
            ->fromModelTable()
            ->addFiltersWithAndToAlias($filters)
            ->addSorts($sorts);
        $expected =
            'SELECT `boards1`.`id_board`, `boards1`.`title`, `boards1`.`created_at`, ' .
            '`boards1`.`updated_at`, `boards1`.`deleted_at` ' .
            'FROM `boards` `boards1` ' .
            'WHERE `boards1`.`created_at` = :dcValue1 ' .
            'ORDER BY `boards1`.`title` ASC';

        $this->assertEquals($expected, $builder->getSQL());
    }

    /**
     * Test builder.
     *
     * @throws Exception
     */
    public function testIndex(): void
    {
        $builder = $this->createModelQueryBuilder(Board::class);

        $builder
            ->selectModelColumns()
            ->fromModelTable();
        $expected =
            'SELECT `boards1`.`id_board`, `boards1`.`title`, `boards1`.`created_at`, ' .
            '`boards1`.`updated_at`, `boards1`.`deleted_at` ' .
            'FROM `boards` `boards1`';

        $this->assertEquals($expected, $builder->getSQL());
    }

    /**
     * Test builder.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testIndexWithFilters(): void
    {
        $filters = [
            Board::FIELD_TITLE => [
                FilterParameterInterface::OPERATION_EQUALS            => ['aaa'],
                FilterParameterInterface::OPERATION_NOT_EQUALS        => ['bbb'],
                FilterParameterInterface::OPERATION_LESS_THAN         => ['ccc'],
                FilterParameterInterface::OPERATION_LESS_OR_EQUALS    => ['ddd'],
                FilterParameterInterface::OPERATION_GREATER_THAN      => ['eee'],
                FilterParameterInterface::OPERATION_GREATER_OR_EQUALS => ['fff'],
                FilterParameterInterface::OPERATION_LIKE              => ['ggg'],
                FilterParameterInterface::OPERATION_NOT_LIKE          => ['hhh'],
                FilterParameterInterface::OPERATION_IN                => ['iii'],
                FilterParameterInterface::OPERATION_NOT_IN            => ['jjj', 'kkk'],
                FilterParameterInterface::OPERATION_IS_NULL           => [],
                FilterParameterInterface::OPERATION_IS_NOT_NULL       => ['whatever'],
            ],
        ];

        $builder = $this->createModelQueryBuilder(Board::class);
        $builder
            ->selectModelColumns()
            ->fromModelTable()
            ->addFiltersWithAndToAlias($filters);

        $expected =
            'SELECT `boards1`.`id_board`, `boards1`.`title`, `boards1`.`created_at`, ' .
            '`boards1`.`updated_at`, `boards1`.`deleted_at` ' .
            'FROM `boards` `boards1` ' .
            'WHERE ' .
            '(`boards1`.`title` = :dcValue1) AND ' .
            '(`boards1`.`title` <> :dcValue2) AND ' .
            '(`boards1`.`title` < :dcValue3) AND ' .
            '(`boards1`.`title` <= :dcValue4) AND ' .
            '(`boards1`.`title` > :dcValue5) AND ' .
            '(`boards1`.`title` >= :dcValue6) AND ' .
            '(`boards1`.`title` LIKE :dcValue7) AND ' .
            '(`boards1`.`title` NOT LIKE :dcValue8) AND ' .
            '(`boards1`.`title` IN (:dcValue9)) AND ' .
            '(`boards1`.`title` NOT IN (:dcValue10, :dcValue11)) AND ' .
            '(`boards1`.`title` IS NULL) AND ' .
            '(`boards1`.`title` IS NOT NULL)';

        $this->assertEquals($expected, $builder->getSQL());
        $this->assertEquals([
            'dcValue1'  => 'aaa',
            'dcValue2'  => 'bbb',
            'dcValue3'  => 'ccc',
            'dcValue4'  => 'ddd',
            'dcValue5'  => 'eee',
            'dcValue6'  => 'fff',
            'dcValue7'  => 'ggg',
            'dcValue8'  => 'hhh',
            'dcValue9'  => 'iii',
            'dcValue10' => 'jjj',
            'dcValue11' => 'kkk',
        ], $builder->getParameters());
    }

    /**
     * Test builder.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testIndexWithFiltersJoinedWithOR(): void
    {
        $filters = [
            Board::FIELD_TITLE => [
                FilterParameterInterface::OPERATION_EQUALS         => ['aaa'],
                FilterParameterInterface::OPERATION_LESS_OR_EQUALS => ['bbb'],
            ],
        ];

        $builder = $this->createModelQueryBuilder(Board::class);
        $builder
            ->selectModelColumns()
            ->fromModelTable()
            ->addFiltersWithOrToAlias($filters);

        $expected =
            'SELECT `boards1`.`id_board`, `boards1`.`title`, `boards1`.`created_at`, ' .
            '`boards1`.`updated_at`, `boards1`.`deleted_at` ' .
            'FROM `boards` `boards1` ' .
            'WHERE (`boards1`.`title` = :dcValue1) OR (`boards1`.`title` <= :dcValue2)';

        $this->assertEquals($expected, $builder->getSQL());
        $this->assertEquals([
            'dcValue1' => 'aaa',
            'dcValue2' => 'bbb',
        ], $builder->getParameters());
    }

    /**
     * Test builder.
     *
     * @throws DBALException
     *
     * @expectedException \Limoncello\Flute\Exceptions\InvalidArgumentException
     */
    public function testReadWithInvalidParam(): void
    {
        $builder = $this->createModelQueryBuilder(Board::class);

        $emptyArguments = [];
        $filters        = [
            Board::FIELD_ID => [
                FilterParameterInterface::OPERATION_EQUALS => $emptyArguments,
            ],
        ];
        $builder
            ->selectModelColumns()
            ->fromModelTable()
            ->addFiltersWithAndToAlias($filters);
    }

    /**
     * Test update with OR condition.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testUpdateWithOrCondition(): void
    {
        $filters = [
            Board::FIELD_ID => [
                FilterParameterInterface::OPERATION_GREATER_OR_EQUALS => [1],
                FilterParameterInterface::OPERATION_LESS_OR_EQUALS    => [5],
            ],
        ];

        $builder = $this->createModelQueryBuilder(Board::class);
        $builder
            ->addFiltersWithOrToTable($filters)
            ->updateModels([
                Board::FIELD_TITLE => 'New title',
            ]);

        $expected =
            'UPDATE `boards` SET `title` = :dcValue3 ' .
            'WHERE (`boards`.`id_board` >= :dcValue1) OR (`boards`.`id_board` <= :dcValue2)';

        $this->assertEquals($expected, $builder->getSQL());
        $this->assertEquals([
            'dcValue1' => 1,
            'dcValue2' => 5,
            'dcValue3' => 'New title',
        ], $builder->getParameters());
    }

    /**
     * Test building quoted column names.
     *
     * @throws Exception
     */
    public function testBuildingQuotedColumns(): void
    {
        $builder = $this->createModelQueryBuilder(Board::class);

        $this->assertEquals('`boards`.`title`', $builder->getQuotedMainTableColumn(Board::FIELD_TITLE));
        $this->assertEquals('`boards1`.`title`', $builder->getQuotedMainAliasColumn(Board::FIELD_TITLE));
    }

    /**
     * @param string $modelClass
     *
     * @return ModelQueryBuilder
     */
    private function createModelQueryBuilder(string $modelClass): ModelQueryBuilder
    {
        return new ModelQueryBuilder($this->getConnection(), $modelClass, $this->getModelSchemes());
    }

    /**
     * @return Connection
     */
    private function getConnection(): Connection
    {
        return $this->connection;
    }
}
