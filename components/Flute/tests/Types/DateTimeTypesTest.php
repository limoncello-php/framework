<?php namespace Limoncello\Tests\Flute\Types;

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
use Doctrine\DBAL\Types\Type;
use Limoncello\Flute\Types\DateJsonApiStringType;
use Limoncello\Flute\Types\DateTimeDefaultNativeType;
use Limoncello\Flute\Types\DateTimeDefaultStringType;
use Limoncello\Flute\Types\DateTimeJsonApiNativeType;
use Limoncello\Flute\Types\DateTimeJsonApiStringType;
use Limoncello\Tests\Flute\TestCase;
use Limoncello\Validation\Validator as v;

/**
 * @package Limoncello\Tests\Flute
 */
class DateTimeTypesTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        if (Type::hasType(DateTimeDefaultNativeType::NAME) === false) {
            Type::addType(DateTimeDefaultNativeType::NAME, DateTimeDefaultNativeType::class);
        }
        if (Type::hasType(DateTimeDefaultStringType::NAME) === false) {
            Type::addType(DateTimeDefaultStringType::NAME, DateTimeDefaultStringType::class);
        }
        if (Type::hasType(DateTimeJsonApiNativeType::NAME) === false) {
            Type::addType(DateTimeJsonApiNativeType::NAME, DateTimeJsonApiNativeType::class);
        }
        if (Type::hasType(DateTimeJsonApiStringType::NAME) === false) {
            Type::addType(DateTimeJsonApiStringType::NAME, DateTimeJsonApiStringType::class);
        }
        if (Type::hasType(DateJsonApiStringType::NAME) === false) {
            Type::addType(DateJsonApiStringType::NAME, DateJsonApiStringType::class);
        }
    }

    /**
     * Test date conversions.
     */
    public function testDefaultNativeConversions()
    {
        $type     = Type::getType(DateTimeDefaultNativeType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $dbDate = '2001-02-03 04:05:06';

        $dateTime = DateTime::createFromFormat(DateTime::ISO8601, '2001-02-03T04:05:06+00:00');
        $dbValue  = $type->convertToDatabaseValue($dateTime, $platform);

        $this->assertEquals($dbDate, $dbValue);

        $phpValue = $type->convertToPHPValue($dbDate, $platform);
        $this->assertEquals($dateTime, $phpValue);

        $this->assertEquals($phpValue, $type->convertToPHPValue($phpValue, $platform));

        // extra coverage for getSQLDeclaration
        $this->assertEquals('DATETIME', $type->getSQLDeclaration([], $platform));

        // extra coverage for `null` inputs
        $this->assertNull($type->convertToPHPValue(null, $platform));
        $this->assertNull($type->convertToDatabaseValue(null, $platform));
    }

    /**
     * Test date conversions.
     */
    public function testDefaultStringConversions()
    {
        $type     = Type::getType(DateTimeDefaultStringType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = '2001-02-03T04:05:06+0000';
        $dbDate   = '2001-02-03 04:05:06';

        $dbValue = $type->convertToDatabaseValue($jsonDate, $platform);
        $this->assertEquals($dbDate, $dbValue);

        $phpValue = $type->convertToPHPValue($dbDate, $platform);
        $this->assertEquals($jsonDate, $phpValue);
    }

    /**
     * Test date conversions.
     */
    public function testJsonApiNativeConversions()
    {
        $type     = Type::getType(DateTimeJsonApiNativeType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = '2001-02-03T04:05:06+0000';
        $dbDate   = '2001-02-03 04:05:06';
        $dateTime = DateTime::createFromFormat(DateTime::ISO8601, '2001-02-03T04:05:06+00:00');

        $dbValue = $type->convertToDatabaseValue($dateTime, $platform);
        $this->assertEquals($dbDate, $dbValue);

        $phpValue = $type->convertToPHPValue($jsonDate, $platform);
        $this->assertEquals($dateTime, $phpValue);
        $this->assertEquals($phpValue, $type->convertToPHPValue($phpValue, $platform));
    }

    /**
     * Test date conversions.
     */
    public function testJsonApiStringConversions()
    {
        $type     = Type::getType(DateTimeJsonApiStringType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = '2001-02-03T04:05:06+0000';
        $dbDate   = '2001-02-03 04:05:06';

        $dbValue = $type->convertToDatabaseValue($jsonDate, $platform);
        $this->assertEquals($dbDate, $dbValue);

        $phpValue = $type->convertToPHPValue($jsonDate, $platform);
        $this->assertEquals($jsonDate, $phpValue);
    }

    /**
     * Test date conversions.
     *
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function testInvalidValueForDefaultNativeConversions()
    {
        $type     = Type::getType(DateTimeDefaultNativeType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $type->convertToPHPValue('2001-02-03 04:05:06.XXX', $platform);
    }

    /**
     * Test date conversions.
     */
    public function testJsonApiStringDateConversions()
    {
        $type     = Type::getType(DateJsonApiStringType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $jsonDate = '2001-02-03T04:05:06+0000';
        $dbDate   = '2001-02-03';

        $dbValue = $type->convertToDatabaseValue($jsonDate, $platform);
        $this->assertEquals($dbDate, $dbValue);

        $dbValue = $type->convertToDatabaseValue(null, $platform);
        $this->assertNull($dbValue);

        $phpValue = $type->convertToPHPValue($jsonDate, $platform);
        $this->assertEquals($jsonDate, $phpValue);
    }

    /**
     * Test date conversions.
     *
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function testInvalidValueForJsonApiStringDateConversions()
    {
        $type     = Type::getType(DateJsonApiStringType::NAME);
        $platform = $this->createConnection()->getDatabasePlatform();

        $type->convertToDatabaseValue('2001-02-03 04:05:06.XXX', $platform);
    }
}
