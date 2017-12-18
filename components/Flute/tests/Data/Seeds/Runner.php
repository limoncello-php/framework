<?php namespace Limoncello\Tests\Flute\Data\Seeds;

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

use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Faker\Factory;
use Limoncello\Tests\Flute\Data\Models\Board;
use Limoncello\Tests\Flute\Data\Models\Category;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Models\CommentEmotion;
use Limoncello\Tests\Flute\Data\Models\Emotion;
use Limoncello\Tests\Flute\Data\Models\Model;
use Limoncello\Tests\Flute\Data\Models\Post;
use Limoncello\Tests\Flute\Data\Models\Role;
use Limoncello\Tests\Flute\Data\Models\User;

/**
 * @package Limoncello\Tests\Flute
 */
class Runner
{
    /**
     * @param Connection $connection
     *
     * @return void
     */
    public function run(Connection $connection)
    {
        $faker = Factory::create();
        $faker->seed(1234);

        $this->seedTable($connection, 5, Board::TABLE_NAME, function () use ($faker) {
            return [
                Board::FIELD_TITLE => 'Board ' . $faker->text(20),
            ];
        });

        $this->seedTable($connection, 5, Role::TABLE_NAME, function () use ($faker) {
            return [
                Role::FIELD_NAME => 'Role ' . $faker->text(20),
            ];
        });

        $this->seedTable($connection, 5, Emotion::TABLE_NAME, function () use ($faker) {
            return [
                Emotion::FIELD_NAME => 'Emotion ' . $faker->text(20),
            ];
        });

        $allRoles = $this->readAll($connection, Role::TABLE_NAME);
        $this->seedTable($connection, 5, User::TABLE_NAME, function () use ($faker, $allRoles) {
            return [
                User::FIELD_ID_ROLE       => $faker->randomElement($allRoles)[Role::FIELD_ID],
                User::FIELD_TITLE         => $faker->title,
                User::FIELD_FIRST_NAME    => $faker->firstName,
                User::FIELD_LAST_NAME     => $faker->lastName,
                User::FIELD_LANGUAGE      => $faker->languageCode,
                User::FIELD_EMAIL         => $faker->email,
                User::FIELD_IS_ACTIVE     => $faker->boolean,
                User::FIELD_PASSWORD_HASH => 'some_hash',
                User::FIELD_API_TOKEN     => 'some_token',
            ];
        });

        $allBoards = $this->readAll($connection, Board::TABLE_NAME);
        $allUsers  = $this->readAll($connection, User::TABLE_NAME);
        $this->seedTable($connection, 20, Post::TABLE_NAME, function () use ($faker, $allBoards, $allUsers) {
            return [
                Post::FIELD_ID_BOARD  => $faker->randomElement($allBoards)[Board::FIELD_ID],
                Post::FIELD_ID_USER   => $faker->randomElement($allUsers)[User::FIELD_ID],
                Post::FIELD_ID_EDITOR => null,
                Post::FIELD_TITLE     => $faker->text(50),
                Post::FIELD_TEXT      => $faker->text(),
            ];
        });

        $allPosts = $this->readAll($connection, Post::TABLE_NAME);
        $this->seedTable($connection, 100, Comment::TABLE_NAME, function () use ($faker, $allPosts, $allUsers) {
            return [
                Comment::FIELD_ID_POST => $faker->randomElement($allPosts)[Post::FIELD_ID],
                Comment::FIELD_ID_USER => $faker->randomElement($allUsers)[User::FIELD_ID],
                Comment::FIELD_TEXT    => $faker->text(),
            ];
        });

        $allComments = $this->readAll($connection, Comment::TABLE_NAME);
        $allEmotions = $this->readAll($connection, Emotion::TABLE_NAME);
        $this->seedTable($connection, 250, CommentEmotion::TABLE_NAME, function () use (
            $faker,
            $allComments,
            $allEmotions
        ) {
            return [
                CommentEmotion::FIELD_ID_COMMENT => $faker->randomElement($allComments)[Comment::FIELD_ID],
                CommentEmotion::FIELD_ID_EMOTION => $faker->randomElement($allEmotions)[Emotion::FIELD_ID],
            ];
        });

        $this->seedRows($connection, Category::TABLE_NAME, [
            [Category::FIELD_NAME => 'Main', Category::FIELD_ID_PARENT => null],
            [Category::FIELD_NAME => 'Sub1', Category::FIELD_ID_PARENT => 1],
            [Category::FIELD_NAME => 'Sub2', Category::FIELD_ID_PARENT => 1],
        ]);
    }

    /**
     * @param Connection $connection
     * @param string     $tableName
     *
     * @return array
     */
    protected function readAll(Connection $connection, $tableName)
    {
        $result = $connection->fetchAll("SELECT * FROM `$tableName`");

        return $result;
    }

    /**
     * @param Connection $connection
     * @param int        $records
     * @param string     $tableName
     * @param Closure    $fieldsClosure
     */
    private function seedTable(Connection $connection, $records, $tableName, Closure $fieldsClosure)
    {
        for ($i = 0; $i !== (int)$records; $i++) {
            $fields = $fieldsClosure();

            $fields = array_merge($fields, [Model::FIELD_CREATED_AT => date('Y-m-d H:i:s')]);
            try {
                $result = $connection->insert($tableName, $fields);
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (UniqueConstraintViolationException $e) {
                // ignore non-unique records
                $result = true;
            }
            $result ?: null;
            assert($result !== false, 'Statement execution failed');
        }
    }

    /**
     * @param Connection $connection
     * @param string     $tableName
     * @param array      $fields
     *
     * @return void
     */
    private function seedRow(Connection $connection, $tableName, array $fields)
    {
        $fields = array_merge($fields, [Model::FIELD_CREATED_AT => date('Y-m-d H:i:s')]);

        $quotedFields = [];
        foreach ($fields as $column => $value) {
            $quotedFields["`$column`"] = $value;
        }

        try {
            $result = $connection->insert($tableName, $quotedFields);
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (UniqueConstraintViolationException $e) {
            // ignore non-unique records
            $result = true;
        }
        if ($result === false) {
            assert($result !== false, 'Statement execution failed');
        }
    }

    /**
     * @param Connection $connection
     * @param string     $tableName
     * @param array      $rows
     *
     * @return void
     */
    private function seedRows(Connection $connection, $tableName, array $rows)
    {
        foreach ($rows as $row) {
            $this->seedRow($connection, $tableName, $row);
        }
    }
}
