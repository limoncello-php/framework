<?php namespace Limoncello\Tests\Flute\Api;

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
use Generator;
use Limoncello\Container\Container;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Flute\Adapters\PaginationStrategy;
use Limoncello\Flute\Contracts\Adapters\PaginationStrategyInterface;
use Limoncello\Flute\Contracts\Api\CrudInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Http\Query\FilterParameterInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Limoncello\Flute\Factory;
use Limoncello\Tests\Flute\Data\Api\CommentsApi;
use Limoncello\Tests\Flute\Data\Api\PostsApi;
use Limoncello\Tests\Flute\Data\Api\StringPKModelApi;
use Limoncello\Tests\Flute\Data\Api\UsersApi;
use Limoncello\Tests\Flute\Data\L10n\FormatterFactory;
use Limoncello\Tests\Flute\Data\Models\Board;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Models\CommentEmotion;
use Limoncello\Tests\Flute\Data\Models\Post;
use Limoncello\Tests\Flute\Data\Models\StringPKModel;
use Limoncello\Tests\Flute\Data\Models\User;
use Limoncello\Tests\Flute\TestCase;
use PDO;
use stdClass;

/**
 * @package Limoncello\Tests\Flute
 */
class CrudTest extends TestCase
{
    const DEFAULT_PAGE = 3;

    const DEFAULT_MAX_PAGE = 100;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Test create read and delete newly created resource.
     */
    public function testCreateReadAndDeletePost()
    {
        $userId     = 1;
        $boardId    = 2;
        $text       = 'Some text';
        $title      = 'Some title';
        $attributes = [
            Post::FIELD_TITLE    => $title,
            Post::FIELD_TEXT     => $text,
            Post::FIELD_ID_BOARD => $boardId,
            Post::FIELD_ID_USER  => $userId,
        ];
        $toMany     = [];

        $crud = $this->createCrud(PostsApi::class);

        $this->assertNotNull($index = $crud->create(null, $attributes, $toMany));
        $this->assertNotNull($model = $crud->read($index)->getData());

        /** @var Post $model */

        $this->assertEquals($userId, $model->{Post::FIELD_ID_USER});
        $this->assertEquals($boardId, $model->{Post::FIELD_ID_BOARD});
        $this->assertEquals($title, $model->{Post::FIELD_TITLE});
        $this->assertEquals($text, $model->{Post::FIELD_TEXT});
        $this->assertNotEmpty($index = $model->{Post::FIELD_ID});

        $this->assertNotNull($crud->read($index)->getData());

        $crud->remove($index);

        $this->assertNull($crud->read($index)->getData());

        // second delete does nothing (already deleted)
        $crud->remove($index);
    }

    /**
     * Test create read and delete newly created resource with string primary key.
     */
    public function testCreateReadAndDeleteStringPKModel()
    {
        $pk         = 'new_pk_value';
        $name       = 'Some title';
        $attributes = [
            StringPKModel::FIELD_NAME => $name,
        ];

        $crud = $this->createCrud(StringPKModelApi::class);

        $this->assertNotNull($index = $crud->create($pk, $attributes, []));
        $this->assertEquals($pk, $index);
        $this->assertNotNull($model = $crud->read($index)->getData());

        /** @var StringPKModel $model */

        $this->assertEquals($pk, $model->{StringPKModel::FIELD_ID});
        $this->assertEquals($name, $model->{StringPKModel::FIELD_NAME});

        $this->assertNotNull($crud->read($index)->getData());

        $crud->remove($index);

        $this->assertNull($crud->read($index)->getData());

        // second delete does nothing (already deleted)
        $crud->remove($index);
    }

    /**
     * @expectedException \Limoncello\Flute\Exceptions\InvalidArgumentException
     */
    public function testInputChecksForCreate()
    {
        $invalidIndex = new stdClass();

        $this->createCrud(CommentsApi::class)->create($invalidIndex, [], []);
    }

    /**
     * @expectedException \Limoncello\Flute\Exceptions\InvalidArgumentException
     */
    public function testInputChecksForUpdate()
    {
        $invalidIndex = new stdClass();

        $this->createCrud(CommentsApi::class)->update($invalidIndex, [], []);
    }

    /**
     * @expectedException \Limoncello\Flute\Exceptions\InvalidArgumentException
     */
    public function testInputChecksForReadDelete()
    {
        $invalidIndex = new stdClass();

        $this->createCrud(CommentsApi::class)->remove($invalidIndex);
    }

    /**
     * @expectedException \Limoncello\Flute\Exceptions\InvalidArgumentException
     */
    public function testInputChecksForRead()
    {
        $invalidIndex = new stdClass();

        $this->createCrud(CommentsApi::class)->read($invalidIndex)->getData();
    }

    /**
     * @expectedException \Limoncello\Flute\Exceptions\InvalidArgumentException
     */
    public function testInputChecksForReadRow()
    {
        $invalidIndex = new stdClass();

        $this->createCrud(CommentsApi::class)->read($invalidIndex)->getData();
    }

    /**
     * @expectedException \Limoncello\Flute\Exceptions\InvalidArgumentException
     */
    public function testInputChecksForHasInRelationship1()
    {
        $invalidIndex = new stdClass();

        $this->createCrud(CommentsApi::class)->hasInRelationship($invalidIndex, Comment::REL_EMOTIONS, '1');
    }

    /**
     * @expectedException \Limoncello\Flute\Exceptions\InvalidArgumentException
     */
    public function testInputChecksForHasInRelationship2()
    {
        $invalidIndex = new stdClass();

        $this->createCrud(CommentsApi::class)->hasInRelationship('1', Comment::REL_EMOTIONS, $invalidIndex);
    }

    /**
     * Test create resource with to-many (belongs-to-many relationships).
     */
    public function testCreateCommentsWithEmotions()
    {
        $userId     = 1;
        $postId     = 2;
        $text       = 'Some text';
        $attributes = [
            Comment::FIELD_TEXT    => $text,
            Comment::FIELD_ID_POST => $postId,
            Comment::FIELD_ID_USER => $userId,
        ];
        $toMany     = [
            Comment::REL_EMOTIONS => ['3', '4'],
        ];

        $crud = $this->createCrud(CommentsApi::class);

        $this->assertNotNull($index = $crud->create(null, $attributes, $toMany));
        $this->assertNotNull($model = $crud->read($index)->getData());

        /** @var Comment $model */

        $this->assertEquals($userId, $model->{Comment::FIELD_ID_USER});
        $this->assertEquals($postId, $model->{Comment::FIELD_ID_POST});
        $this->assertEquals($text, $model->{Comment::FIELD_TEXT});
        $this->assertNotEmpty($index = $model->{Comment::FIELD_ID});

        // check resources is saved
        /** @noinspection SqlDialectInspection */
        $res = $this->connection
            ->query('SELECT * FROM ' . Comment::TABLE_NAME . ' WHERE ' . Comment::FIELD_ID . " = $index")
            ->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEquals(false, $res);
        $this->assertEquals($userId, $res[Comment::FIELD_ID_USER]);
        $this->assertEquals($postId, $res[Comment::FIELD_ID_POST]);
        // check resource to-many relationship are saved
        /** @noinspection SqlDialectInspection */
        $res = $this->connection->query(
            'SELECT * FROM ' . CommentEmotion::TABLE_NAME . ' WHERE ' . CommentEmotion::FIELD_ID_COMMENT . " = $index"
        )->fetchAll(PDO::FETCH_ASSOC);
        $this->assertNotEquals(false, $res);
        $this->assertCount(2, $res);

        // same checks but this time via API
        $includePaths = [
            [Comment::REL_USER],
            [Comment::REL_POST],
            [Comment::REL_EMOTIONS],
        ];
        $this->assertNotNull(
            $comment = $crud->withIncludes($includePaths)->read($index)->getData()
        );
        $this->assertEquals(
            $userId,
            $comment->{Comment::REL_USER}->{User::FIELD_ID}
        );
        $this->assertEquals(
            $postId,
            $comment->{Comment::REL_POST}->{Post::FIELD_ID}
        );
        /** @var PaginatedDataInterface $emotions */
        $emotions = $comment->{Comment::REL_EMOTIONS};
        $this->assertCount(2, $emotions->getData());
        $this->assertFalse($emotions->hasMoreItems());
        $this->assertSame(0, $emotions->getOffset());
        $this->assertSame(self::DEFAULT_PAGE, $emotions->getLimit());
    }

    /**
     * Test update resource with to-many (belongs-to-many relationships).
     */
    public function testUpdateCommentsWithEmotions()
    {
        $commentId  = 1;
        $userId     = 1;
        $postId     = 3;
        $text       = 'Some text';
        $attributes = [
            Comment::FIELD_TEXT    => $text,
            Comment::FIELD_ID_POST => $postId,
            Comment::FIELD_ID_USER => $userId,
        ];
        $toMany     = [
            Comment::REL_EMOTIONS => ['3', '4'],
        ];

        $crud = $this->createCrud(CommentsApi::class);

        $changedRecords = $crud->update($commentId, $attributes, $toMany);
        $this->assertEquals(3, $changedRecords);
        $this->assertNotNull($model = $crud->read($commentId)->getData());

        /** @var Comment $model */

        $this->assertEquals($userId, $model->{Comment::FIELD_ID_USER});
        $this->assertEquals($postId, $model->{Comment::FIELD_ID_POST});
        $this->assertEquals($text, $model->{Comment::FIELD_TEXT});
        $this->assertEquals($commentId, $model->{Comment::FIELD_ID});

        $includePaths = [
            [Comment::REL_USER],
            [Comment::REL_POST],
            [Comment::REL_EMOTIONS],
        ];
        $this->assertNotNull(
            $comment = $crud->withIncludes($includePaths)->read($commentId)->getData()
        );
        $this->assertEquals(
            $userId,
            $comment->{Comment::REL_USER}->{User::FIELD_ID}
        );
        $this->assertEquals(
            $postId,
            $comment->{Comment::REL_POST}->{Post::FIELD_ID}
        );
        /** @var PaginatedDataInterface $emotions */
        $emotions = $comment->{Comment::REL_EMOTIONS};
        $this->assertCount(2, $emotions->getData());
        $this->assertFalse($emotions->hasMoreItems());
        $this->assertSame(0, $emotions->getOffset());
        $this->assertSame(self::DEFAULT_PAGE, $emotions->getLimit());
    }

    /**
     * @expectedException \Doctrine\DBAL\Exception\DriverException
     */
    public function testDeleteResourceWithConstraints()
    {
        $crud = $this->createCrud(PostsApi::class);
        $crud->remove(1);
    }

    /**
     * Check 'read' with included paths.
     */
    public function testReadWithIncludes()
    {
        $crud = $this->createCrud(PostsApi::class);

        $index        = 18;
        $includePaths = [
            [Post::REL_BOARD],
            [Post::REL_COMMENTS],
            [Post::REL_COMMENTS, Comment::REL_EMOTIONS],
            [Post::REL_COMMENTS, Comment::REL_POST, Post::REL_USER],
        ];
        $this->assertNotNull(
            $model = $crud->withIncludes($includePaths)->read($index)->getData()
        );

        $board = $model->{Post::REL_BOARD};
        $this->assertEquals(Board::class, get_class($board));
        $this->assertEquals($model->{Post::FIELD_ID_BOARD}, $board->{Board::FIELD_ID});

        /** @var PaginatedDataInterface $commentsRel */
        $commentsRel = $model->{Post::REL_COMMENTS};
        $comments    = $commentsRel->getData();
        $hasMore     = $commentsRel->hasMoreItems();
        $offset      = $commentsRel->getOffset();
        $limit       = $commentsRel->getLimit();
        $this->assertNotEmpty($comments);
        $this->assertCount(3, $comments);
        $this->assertEquals(Comment::class, get_class($comments[0]));
        $this->assertEquals($index, $comments[0]->{Comment::FIELD_ID_POST});
        $this->assertTrue($hasMore);
        $this->assertCount(self::DEFAULT_PAGE, $comments);
        $this->assertEquals(0, $offset);
        $this->assertEquals(self::DEFAULT_PAGE, $limit);

        /** @var PaginatedDataInterface $emotions */
        $emotions = $comments[0]->{Comment::REL_EMOTIONS};
        $this->assertCount(3, $emotions->getData());
        $this->assertTrue($emotions->hasMoreItems());
        $this->assertEquals(0, $emotions->getOffset());
        $this->assertEquals(self::DEFAULT_PAGE, $emotions->getLimit());

        $emotions = $comments[1]->{Comment::REL_EMOTIONS};
        $this->assertCount(1, $emotions->getData());
        $this->assertFalse($emotions->hasMoreItems());
        $this->assertSame(0, $emotions->getOffset());
        $this->assertSame(self::DEFAULT_PAGE, $emotions->getLimit());

        $comment  = $comments[2];
        $emotions = $comment->{Comment::REL_EMOTIONS};
        $this->assertCount(1, $emotions->getData());
        $this->assertFalse($emotions->hasMoreItems());
        $this->assertSame(0, $emotions->getOffset());
        $this->assertSame(self::DEFAULT_PAGE, $emotions->getLimit());

        $this->assertNotNull($post = $comment->{Comment::REL_POST});
        $this->assertNotNull($user = $post->{Post::REL_USER});

        // check no data for relationships we didn't asked to download
        $this->assertFalse(property_exists($user, User::REL_ROLE));
        $this->assertFalse(property_exists($user, User::REL_COMMENTS));
    }

    /**
     * Check 'read' with included paths where could be nulls.
     */
    public function testReadWithNullableInclude()
    {
        $crud = $this->createCrud(PostsApi::class);

        $index = 18;

        // check that editor relationship for selected post is `null`
        /** @noinspection SqlDialectInspection */
        $query    = 'SELECT ' . Post::FIELD_ID_EDITOR . ' FROM ' . Post::TABLE_NAME .
            ' WHERE ' . Post::FIELD_ID . " = $index";
        $idEditor = $this->connection->query($query)->fetch(PDO::FETCH_NUM)[0];
        $this->assertNull($idEditor);

        $includePaths = [
            [Post::REL_EDITOR],
        ];

        $this->assertNotNull(
            $model = $crud->withIncludes($includePaths)->read($index)->getData()
        );
        $this->assertNull($model->{Post::REL_EDITOR});
    }

    /**
     * Test index.
     */
    public function testIndex()
    {
        $crud = $this->createCrud(PostsApi::class);

        $includePaths = [
            [Post::REL_BOARD],
            [Post::REL_COMMENTS],
            [Post::REL_COMMENTS, Comment::REL_EMOTIONS],
            [Post::REL_COMMENTS, Comment::REL_POST, Post::REL_USER],
        ];

        $sortParameters = [
            Post::FIELD_ID_BOARD => false,
            Post::FIELD_TITLE    => true,
        ];
        $pagingOffset   = 1;
        $pagingSize     = 2;
        $filters        = [
            Post::FIELD_TITLE   => [
                FilterParameterInterface::OPERATION_LIKE => ['%'],
            ],
            Post::FIELD_ID_USER => [
                FilterParameterInterface::OPERATION_LESS_THAN => ['5'],
            ],
        ];

        $data = $crud
            ->withFilters($filters)
            ->combineWithAnd()
            ->withSorts($sortParameters)
            ->withIncludes($includePaths)
            ->withPaging($pagingOffset, $pagingSize)
            ->index();

        $this->assertNotEmpty($data->getData());
        $this->assertCount($pagingSize, $data->getData());
        $this->assertEquals(20, $data->getData()[0]->{Post::FIELD_ID});
        $this->assertEquals(9, $data->getData()[1]->{Post::FIELD_ID});
        $this->assertTrue($data->isCollection());
        $this->assertEquals($pagingOffset, $data->getOffset());
        $this->assertEquals($pagingSize, $data->getLimit());
    }

    /**
     * Test index.
     */
    public function testIndexFilterOperationOnRelationship()
    {
        $crud = $this->createCrud(PostsApi::class);

        $pagingOffset = 0;
        $pagingSize   = 20;
        $filters      = [
            Post::FIELD_ID_USER => [
                FilterParameterInterface::OPERATION_IN => [2, 4],
            ],
        ];

        $data = $crud->withFilters($filters)->withPaging($pagingOffset, $pagingSize)->index();

        $this->assertCount(6, $data->getData());
    }

    /**
     * Test index.
     */
    public function testCommentsIndex()
    {
        // check that API returns comments from only specific user (as configured in Comments API)
        $expectedUserId = 1;

        $crud = $this->createCrud(CommentsApi::class);

        $data = $crud->index();

        $this->assertNotEmpty($comments = $data->getData());
        foreach ($comments as $comment) {
            $this->assertEquals($expectedUserId, $comment->{Comment::FIELD_ID_USER});
        }
    }

    /**
     * Test read relationship.
     */
    public function testReadRelationship()
    {
        $crud = $this->createCrud(PostsApi::class);

        $pagingOffset   = 1;
        $pagingSize     = 2;
        $postFilters    = [
            Post::FIELD_ID => [
                FilterParameterInterface::OPERATION_EQUALS => [1],
            ],
        ];
        $commentFilters = [
            Comment::FIELD_ID_USER => [
                FilterParameterInterface::OPERATION_LESS_THAN => [5],
            ],
            Comment::FIELD_TEXT    => [
                FilterParameterInterface::OPERATION_LIKE => ['%'],
            ],
        ];
        $commentSorts   = [
            Comment::FIELD_ID_USER => false,
            Comment::FIELD_TEXT    => true,
        ];

        $data = $crud
            ->withFilters($postFilters)
            ->withPaging($pagingOffset, $pagingSize)
            ->indexRelationship(Post::REL_COMMENTS, $commentFilters, $commentSorts);

        $this->assertNotEmpty($data->getData());
        $this->assertCount($pagingSize, $data->getData());
        $this->assertEquals(9, $data->getData()[0]->{Comment::FIELD_ID});
        $this->assertEquals(85, $data->getData()[1]->{Comment::FIELD_ID});
        $this->assertTrue($data->isCollection());
        $this->assertEquals($pagingOffset, $data->getOffset());
        $this->assertEquals($pagingSize, $data->getLimit());
    }

    /**
     * Test read relationship.
     */
    public function testReadRelationshipIdentities()
    {
        $crud = $this->createCrud(PostsApi::class);

        $pagingOffset   = 1;
        $pagingSize     = 2;
        $postFilters    = [
            Post::FIELD_ID => [
                FilterParameterInterface::OPERATION_EQUALS => [1],
            ],
        ];
        $commentFilters = [
            Comment::FIELD_ID_USER => [
                FilterParameterInterface::OPERATION_LESS_THAN => [5],
            ],
            Comment::FIELD_TEXT    => [
                FilterParameterInterface::OPERATION_LIKE => ['%'],
            ],
        ];
        $commentSorts   = [
            Comment::FIELD_ID_USER => false,
            Comment::FIELD_TEXT    => true,
        ];

        $data = $crud
            ->withFilters($postFilters)
            ->withPaging($pagingOffset, $pagingSize)
            ->indexRelationshipIdentities(Post::REL_COMMENTS, $commentFilters, $commentSorts);

        $this->assertEquals([9, 85, 83], $data);
    }

    /**
     * Test index.
     */
    public function testIndexWithFilterByBooleanColumn()
    {
        $crud = $this->createCrud(UsersApi::class);

        $filters = [
            User::FIELD_IS_ACTIVE => [
                FilterParameterInterface::OPERATION_EQUALS => [true],
            ],
        ];

        $data  = $crud->withFilters($filters)->index();
        $users = $data->getData();
        $this->assertNotEmpty($users);

        /** @noinspection SqlDialectInspection */
        $query   = 'SELECT COUNT(*) FROM ' . User::TABLE_NAME . ' WHERE ' . User::FIELD_IS_ACTIVE . ' = 1';
        $actives = $this->connection->query($query)->fetch(PDO::FETCH_NUM)[0];

        $this->assertEquals($actives, count($users));
    }

    /**
     * Test index.
     */
    public function testIndexWithEqualsOperator()
    {
        $crud = $this->createCrud(PostsApi::class);

        $index   = 2;
        $filters = [
            Post::FIELD_ID => [
                FilterParameterInterface::OPERATION_EQUALS => [$index],
            ],
        ];

        $data = $crud->withFilters($filters)->index();

        $this->assertNotEmpty($data->getData());
        $this->assertCount(1, $data->getData());
        $this->assertEquals($index, $data->getData()[0]->{Post::FIELD_ID});
        $this->assertTrue($data->isCollection());
    }

    /**
     * Test read typed row.
     */
    public function testReadRow()
    {
        $crud = $this->createCrud(PostsApi::class);

        $builder = $crud->withIndexFilter(1)->createIndexBuilder();
        $row     = $crud->fetchRow($builder, Post::class);

        $this->assertTrue(is_int($row[Post::FIELD_ID_BOARD]));
        $this->assertTrue(is_string($row[Post::FIELD_TEXT]));
    }

    /**
     * Test read typed row.
     */
    public function testReadColumn()
    {
        $crud = $this->createCrud(PostsApi::class);

        $column = $crud
            ->withFilters([
                Post::FIELD_ID => [
                    FilterParameterInterface::OPERATION_GREATER_OR_EQUALS => [5],
                    FilterParameterInterface::OPERATION_LESS_OR_EQUALS    => [8],
                ],
            ])
            ->withSorts([
                Post::FIELD_ID => false,
            ])
            ->indexIdentities();

        $this->assertEquals([8, 7, 6, 5], $column);
    }

    /**
     * Test index.
     */
    public function testCount()
    {
        $crud = $this->createCrud(PostsApi::class);

        $filters = [
            Post::FIELD_ID_USER => [
                FilterParameterInterface::OPERATION_LESS_THAN => ['5'],
            ],
            Post::FIELD_TITLE   => [
                FilterParameterInterface::OPERATION_LIKE => ['%'],
            ],
        ];

        $result = $crud->withFilters($filters)->count();

        $this->assertEquals(14, $result);
    }

    /**
     * Test check resource exists in relationship.
     */
    public function testHasInRelationship()
    {
        $crud = $this->createCrud(PostsApi::class);

        $this->assertFalse($crud->hasInRelationship(1, Post::REL_COMMENTS, 1));
        $this->assertTrue($crud->hasInRelationship(1, Post::REL_COMMENTS, 9));
    }

    /**
     * @param string $class
     *
     * @return CrudInterface
     */
    private function createCrud($class)
    {
        $container = new Container();

        $container[FormatterFactoryInterface::class] = $formatterFactory = new FormatterFactory();
        $container[Connection::class]                = $this->connection = $this->initDb();
        $container[FactoryInterface::class]          = $factory = new Factory($container);
        $container[ModelSchemeInfoInterface::class]  = $modelSchemes = $this->getModelSchemes();

        $container[PaginationStrategyInterface::class] = new PaginationStrategy(
            self::DEFAULT_PAGE,
            self::DEFAULT_MAX_PAGE
        );

        $crud = new $class($container);

        return $crud;
    }

    /**
     * @param iterable $iterable
     *
     * @return array
     */
    private function iterableToArray(iterable $iterable): array
    {
        $result = [];

        foreach ($iterable as $key => $value) {
            $result[$key] = $value instanceof Generator ? $this->iterableToArray($value) : $value;
        }

        return $result;
    }
}
