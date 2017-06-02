<?php namespace Limoncello\Tests\Passport\Adaptors\MySql;

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
use Limoncello\Passport\Adaptors\MySql\Token;
use Limoncello\Passport\Entities\DatabaseScheme;
use PDO;

/**
 * Class TokenTest
 *
 * @package Limoncello\Tests\Passport
 */
class TokenTest extends TestCase
{
    /**
     * Test client's constructor.
     */
    public function testConstructor()
    {
        $connection = $this->createConnection();
        $types      = [
            Token::FIELD_ID                => Type::INTEGER,
            Token::FIELD_ID_CLIENT         => Type::STRING,
            Token::FIELD_ID_USER           => Type::INTEGER,
            Token::FIELD_REDIRECT_URI      => Type::STRING,
            Token::FIELD_CODE              => Type::STRING,
            Token::FIELD_TYPE              => Type::STRING,
            Token::FIELD_VALUE             => Type::STRING,
            Token::FIELD_REFRESH           => Type::STRING,
            Token::FIELD_IS_SCOPE_MODIFIED => Type::BOOLEAN,
            Token::FIELD_IS_ENABLED        => Type::BOOLEAN,

            Token::FIELD_SCOPES => Type::STRING,
        ];
        $columns    = [
            Token::FIELD_ID                => 123,
            Token::FIELD_ID_CLIENT         => 'some_client_id',
            Token::FIELD_ID_USER           => 321,
            Token::FIELD_REDIRECT_URI      => 'https://acme.foo/redirect',
            Token::FIELD_CODE              => 'some_code',
            Token::FIELD_TYPE              => 'code',
            Token::FIELD_VALUE             => 'some_value',
            Token::FIELD_REFRESH           => 'some_value',
            Token::FIELD_IS_SCOPE_MODIFIED => false,
            Token::FIELD_IS_ENABLED        => true,
            Token::FIELD_SCOPES            => 'one two three',
        ];

        $this->createTable($connection, DatabaseScheme::TABLE_CLIENTS, $types);
        $connection->insert(DatabaseScheme::TABLE_CLIENTS, $columns, $types);

        // now read from SqLite table as it was MySql view or table
        $query     = $connection->createQueryBuilder();
        $statement = $query
            ->select(['*'])
            ->from(DatabaseScheme::TABLE_CLIENTS)
            ->setMaxResults(1)
            ->execute();
        $statement->setFetchMode(PDO::FETCH_CLASS, Token::class);
        $this->assertCount(1, $clients = $statement->fetchAll());

        /** @var Token $client */
        $client = $clients[0];
        $this->assertEquals(['one', 'two', 'three'], $client->getScopeIdentifiers());
    }
}
