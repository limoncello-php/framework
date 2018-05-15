<?php namespace Limoncello\Tests\Passport\Adaptors\MySql;

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

use Doctrine\DBAL\Connection;
use Exception;
use Limoncello\Passport\Adaptors\MySql\Client;
use Limoncello\Passport\Adaptors\MySql\ClientRepository;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemaInterface;
use Mockery;
use ReflectionMethod;

/**
 * @package Limoncello\Tests\Passport
 */
class ClientRepositoryTest extends TestCase
{
    /**
     * Test getters.
     *
     * @throws Exception
     */
    public function testGetters()
    {
        $connection = Mockery::mock(Connection::class);
        /** @var Mockery\Mock $schema */
        $schema = Mockery::mock(DatabaseSchemaInterface::class);

        $viewName = 'whatever';
        $schema->shouldReceive('getClientsView')->once()->withNoArgs()->andReturn($viewName);

        /** @var Connection $connection */
        /** @var DatabaseSchemaInterface $schema */

        $repository = new ClientRepository($connection, $schema);

        $method = new ReflectionMethod(ClientRepository::class, 'getClassName');
        $method->setAccessible(true);
        $this->assertEquals(Client::class, $method->invoke($repository));

        $method = new ReflectionMethod(ClientRepository::class, 'getTableNameForReading');
        $method->setAccessible(true);
        $this->assertEquals($viewName, $method->invoke($repository));
    }
}
