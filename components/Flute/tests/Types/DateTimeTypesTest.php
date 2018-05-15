<?php namespace Limoncello\Tests\Flute\Types;

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

use DateTime;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Exception;
use Limoncello\Flute\Types\DateTime as JsonApiDateTime;
use Limoncello\Flute\Types\DateTimeType as JsonApiDateTimeType;
use Limoncello\Flute\Types\DateType as JsonApiDateType;
use Limoncello\Tests\Flute\TestCase;

/**
 * @package Limoncello\Tests\Flute
 */
class DateTimeTypesTest extends TestCase
{
    /**
     * @inheritdoc
     *
     * @throws DBALException
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (Type::hasType(JsonApiDateTimeType::NAME) === false) {
            Type::addType(JsonApiDateTimeType::NAME, JsonApiDateTimeType::class);
        }
        if (Type::hasType(JsonApiDateType::NAME) === false) {
            Type::addType(JsonApiDateType::NAME, JsonApiDateType::class);
        }
    }

    /**
     * Test date conversions.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testJsonApiDateTimeTypeConversions(): void
    {
        $type     = Type::getType(JsonApiDateTimeType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = '2001-02-03T04:05:06+0000';

        /** @var JsonApiDateTime $phpValue */
        $phpValue = $type->convertToPHPValue($jsonDate, $platform);
        $this->assertEquals(981173106, $phpValue->getTimestamp());
        $this->assertEquals($jsonDate, $phpValue->jsonSerialize());
    }

    /**
     * Test date conversions.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testJsonApiDateTimeTypeToDatabaseConversions1(): void
    {
        $type     = Type::getType(JsonApiDateTimeType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = '2001-02-03T04:05:06+0000';

        /** @var string $phpValue */
        $phpValue = $type->convertToDatabaseValue($jsonDate, $platform);
        $this->assertEquals('2001-02-03 04:05:06', $phpValue);
    }

    /**
     * Test date conversions.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testJsonApiDateTimeTypeToDatabaseConversions2(): void
    {
        $type     = Type::getType(JsonApiDateTimeType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = new DateTime('2001-02-03 04:05:06');

        /** @var string $phpValue */
        $phpValue = $type->convertToDatabaseValue($jsonDate, $platform);
        $this->assertEquals('2001-02-03 04:05:06', $phpValue);
    }

    /**
     * Test date conversions.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testJsonApiDateTimeTypeToDatabaseConversions3(): void
    {
        /** @var JsonApiDateTimeType $type */
        $type     = Type::getType(JsonApiDateTimeType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = new JsonApiDateTime('2001-02-03 04:05:06');

        /** @var string $phpValue */
        $phpValue = $type->convertToDatabaseValue($jsonDate, $platform);
        $this->assertEquals('2001-02-03 04:05:06', $phpValue);
    }

    /**
     * Test date conversions.
     *
     * @throws Exception
     * @throws DBALException
     *
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function testJsonApiDateTimeTypeToDatabaseConversionsInvalidInput1(): void
    {
        $type     = Type::getType(JsonApiDateTimeType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = 'XXX';

        $type->convertToDatabaseValue($jsonDate, $platform);
    }

    /**
     * Test date conversions.
     *
     * @throws Exception
     * @throws DBALException
     *
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function testJsonApiDateTimeTypeToDatabaseConversionsInvalidInput2(): void
    {
        $type     = Type::getType(JsonApiDateTimeType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = new \stdClass();

        $type->convertToDatabaseValue($jsonDate, $platform);
    }

    /**
     * Test date conversions.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testJsonApiDateTypeConversions(): void
    {
        $type     = Type::getType(JsonApiDateType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = '2001-02-03';

        /** @var JsonApiDateTime $phpValue */
        $phpValue = $type->convertToPHPValue($jsonDate, $platform);
        $this->assertEquals(981158400, $phpValue->getTimestamp());
        $this->assertEquals('2001-02-03T00:00:00+0000', $phpValue->jsonSerialize());
    }

    /**
     * Test date conversions.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testJsonApiDateTypeToDatabaseConversions1(): void
    {
        /** @var JsonApiDateType $type */
        $type     = Type::getType(JsonApiDateType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = '2001-02-03T04:05:06+0000';

        /** @var string $phpValue */
        $phpValue = $type->convertToDatabaseValue($jsonDate, $platform);
        $this->assertEquals('2001-02-03', $phpValue);
    }

    /**
     * Test date conversions.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testJsonApiDateTypeToDatabaseConversions2(): void
    {
        /** @var JsonApiDateType $type */
        $type     = Type::getType(JsonApiDateType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = new DateTime('2001-02-03 04:05:06');

        /** @var string $phpValue */
        $phpValue = $type->convertToDatabaseValue($jsonDate, $platform);
        $this->assertEquals('2001-02-03', $phpValue);
    }

    /**
     * Test date conversions.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testJsonApiDateTypeToDatabaseConversions3(): void
    {
        /** @var JsonApiDateType $type */
        $type     = Type::getType(JsonApiDateType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = new JsonApiDateTime('2001-02-03');

        /** @var string $phpValue */
        $phpValue = $type->convertToDatabaseValue($jsonDate, $platform);
        $this->assertEquals('2001-02-03', $phpValue);
    }

    /**
     * Test date conversions.
     *
     * @throws Exception
     * @throws DBALException
     *
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function testJsonApiDateTypeToDatabaseConversionsInvalidInput1(): void
    {
        $type     = Type::getType(JsonApiDateType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = 'XXX';

        $type->convertToDatabaseValue($jsonDate, $platform);
    }

    /**
     * Test date conversions.
     *
     * @throws Exception
     * @throws DBALException
     *
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function testJsonApiDateTypeToDatabaseConversionsInvalidInput2(): void
    {
        $type     = Type::getType(JsonApiDateType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = new \stdClass();

        $type->convertToDatabaseValue($jsonDate, $platform);
    }
}
