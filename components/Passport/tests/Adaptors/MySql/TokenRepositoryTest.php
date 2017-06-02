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

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Limoncello\Passport\Adaptors\MySql\TokenRepository;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use Limoncello\Passport\Entities\DatabaseScheme;
use Limoncello\Tests\Passport\Data\User;

/**
 * @package Limoncello\Tests\Passport
 */
class TokenRepositoryTest extends TestCase
{
    const TEST_TOKEN_VALUE = 'some_token';

    /**
     * Test read passport.
     */
    public function testReadPassport()
    {
        $connection = $this->createConnection();
        $scheme     = new DatabaseScheme('users_table', 'id_user');
        $this->preparePassportTable($connection, $scheme);


        /** @var Connection $connection */
        /** @var DatabaseSchemeInterface $scheme */

        $repository = new TokenRepository($connection, $scheme);
        $this->assertNotEmpty($repository->readPassport(self::TEST_TOKEN_VALUE, 3600));
    }

    /**
     * Test read user.
     */
    public function testReadUserUntyped()
    {
        $connection = $this->createConnection();
        $scheme     = new DatabaseScheme('users_table', 'id_user');
        $this->prepareUserTable($connection, $scheme);


        /** @var Connection $connection */
        /** @var DatabaseSchemeInterface $scheme */

        $repository = new TokenRepository($connection, $scheme);
        $this->assertNotEmpty($repository->readUserByToken(self::TEST_TOKEN_VALUE, 3600, User::class));
        $this->assertEquals(
            [null, null],
            $repository->readUserByToken(self::TEST_TOKEN_VALUE . 'xxx', 3600, User::class)
        );
    }

    /**
     * Test read user.
     */
    public function testReadUserTyped()
    {
        $connection = $this->createConnection();
        $scheme     = new DatabaseScheme('users_table', 'id_user');
        $this->prepareUserTable($connection, $scheme);


        /** @var Connection $connection */
        /** @var DatabaseSchemeInterface $scheme */

        $types      = [
            $scheme->getUsersIdentityColumn() => Type::getType(Type::INTEGER),
        ];
        $repository = new TokenRepository($connection, $scheme);
        $this->assertNotEmpty($repository->readUserByToken(self::TEST_TOKEN_VALUE, 3600, User::class, $types));
        $this->assertEquals(
            [null, null],
            $repository->readUserByToken(self::TEST_TOKEN_VALUE . 'xxx', 3600, User::class, $types)
        );
    }

    /**
     * @param Connection     $connection
     * @param DatabaseScheme $scheme
     *
     * @return void
     */
    private function preparePassportTable(Connection $connection, DatabaseScheme $scheme)
    {
        // emulate view with table
        $types = [
            $scheme->getTokensIdentityColumn()       => Type::INTEGER,
            $scheme->getTokensValueColumn()          => Type::STRING,
            $scheme->getTokensViewScopesColumn()     => Type::STRING,
            $scheme->getTokensIsEnabledColumn()      => Type::BOOLEAN,
            $scheme->getTokensValueCreatedAtColumn() => Type::DATETIME,
        ];
        $data  = [
            $scheme->getTokensIdentityColumn()       => 1,
            $scheme->getTokensValueColumn()          => self::TEST_TOKEN_VALUE,
            $scheme->getTokensViewScopesColumn()     => 'one two three',
            $scheme->getTokensIsEnabledColumn()      => true,
            $scheme->getTokensValueCreatedAtColumn() => new DateTimeImmutable(),
        ];

        $this->createTable($connection, $scheme->getPassportView(), $types);
        $connection->insert($scheme->getPassportView(), $data, $types);
    }

    /**
     * @param Connection     $connection
     * @param DatabaseScheme $scheme
     *
     * @return void
     */
    private function prepareUserTable(Connection $connection, DatabaseScheme $scheme)
    {
        // emulate view with table
        $types = [
            $scheme->getUsersIdentityColumn()        => Type::INTEGER,
            $scheme->getTokensValueColumn()          => Type::STRING,
            $scheme->getClientsViewScopesColumn()    => Type::STRING,
            $scheme->getTokensValueCreatedAtColumn() => Type::DATETIME,
        ];
        $data  = [
            $scheme->getUsersIdentityColumn()        => 1,
            $scheme->getTokensValueColumn()          => self::TEST_TOKEN_VALUE,
            $scheme->getClientsViewScopesColumn()    => 'one two three',
            $scheme->getTokensValueCreatedAtColumn() => new DateTimeImmutable(),
        ];

        $this->createTable($connection, $scheme->getUsersView(), $types);
        $connection->insert($scheme->getUsersView(), $data, $types);
    }
}
