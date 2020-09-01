<?php declare(strict_types=1);

namespace Limoncello\Tests\Flute\Types;

use Doctrine\DBAL\Types\Type;
use Limoncello\Flute\Types\UuidType;
use Limoncello\Tests\Flute\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * Copyright 2020 info@lolltec.com
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

/**
 * @package Limoncello\Tests\Flute
 */
class UuidTypesTest extends TestCase
{
    /**
     * @inheritDoc
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (Type::hasType(UuidType::NAME) === false)
            Type::addType(UuidType::NAME, UuidType::class);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testUuidTypeConversions(): void
    {
        $type = Type::getType(UuidType::NAME);

        $platform = $this->createConnection()->getDatabasePlatform();

        $uuid = Uuid::getFactory()->uuid4()->toString();

        /** @var Uuid $phpValue */
        $phpValue = $type->convertToPHPValue($uuid, $platform);

        $this->assertEquals($uuid, $phpValue);
        $this->assertEquals($uuid, $phpValue->jsonSerialize());
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testUuidTypeToDatabaseConversions(): void
    {
        $type = Type::getType(UuidType::NAME);

        $platform = $this->createConnection()->getDatabasePlatform();

        $uuid = Uuid::getFactory()->uuid4()->toString();

        /** @var string $databaseValue */
        $databaseValue = $type->convertToDatabaseValue($uuid, $platform);
        $this->assertEquals($uuid, $databaseValue);
    }
}
