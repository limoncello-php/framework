<?php declare (strict_types = 1);

namespace Limoncello\Tests\Flute\Validation;

/**
 * Copyright 2015-2019 info@neomerx.com
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
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Exception;
use Limoncello\Container\Container;
use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemasInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiDataParserInterface;
use Limoncello\Flute\Factory;
use Limoncello\Flute\L10n\Messages;
use Limoncello\Flute\Types\DateTime;
use Limoncello\Flute\Validation\JsonApi\DataParser;
use Limoncello\Flute\Validation\JsonApi\Execution\JsonApiDataRulesSerializer;
use Limoncello\Flute\Validation\JsonApi\Execution\JsonApiErrorCollection;
use Limoncello\Flute\Validation\JsonApi\Execution\JsonApiQueryRulesSerializer;
use Limoncello\Tests\Flute\Data\L10n\FormatterFactory;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Schemas\CommentSchema;
use Limoncello\Tests\Flute\Data\Schemas\EmotionSchema;
use Limoncello\Tests\Flute\Data\Schemas\UserSchema;
use Limoncello\Tests\Flute\Data\Validation\AppRules as v;
use Limoncello\Tests\Flute\TestCase;
use Limoncello\Validation\Blocks\ProcedureBlock;
use Limoncello\Validation\Contracts\Blocks\ExecutionBlockInterface;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Execution\BlockSerializer;
use Limoncello\Validation\Execution\ContextStorage;
use Limoncello\Validation\Rules\BaseRule;
use Limoncello\Validation\Rules\Generic\Success;
use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Neomerx\JsonApi\Schema\Error;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionMethod;

/**
 * @package Limoncello\Tests\Flute
 */
class DataParserTest extends TestCase
{
    /**
     * Validation test.
     *
     * @throws Exception
     */
    public function testCaptureValidData(): void
    {
        $text      = 'Outside every fat man there was an even fatter man trying to close in';
        $int       = 123;
        $float     = 3.21;
        $bool      = true;
        $now       = new DateTimeImmutable();
        $dateTime  = $now->format(DateTime::JSON_API_FORMAT);
        $jsonInput = <<<EOT
        {
            "data" : {
                "type"  : "comments",
                "id"    : null,
                "attributes" : {
                    "text-attribute"      : "$text",
                    "int-attribute"       : "$int",
                    "float-attribute"     : "$float",
                    "bool-attribute"      : "$bool",
                    "date-time-attribute" : "$dateTime"
                },
                "relationships" : {
                    "user-relationship" : {
                        "data" : { "type" : "users", "id" : "9" }
                    },
                    "emotions-relationship" : {
                        "data" : [
                            { "type": "emotions", "id":"5" },
                            { "type": "emotions", "id":"12" }
                        ]
                    }
                }
            }
        }
EOT;
        $input     = json_decode($jsonInput, true);

        $container = $this->createContainer();
        $validator = $this->createParser(
            $container,
            'some_rule_name',
            v::equals(null),
            v::equals('comments'),
            [
                CommentSchema::ATTR_TEXT      => v::isString(v::stringLengthMin(1)),
                CommentSchema::ATTR_INT       => v::isString(v::stringToInt()),
                CommentSchema::ATTR_FLOAT     => v::isString(v::stringToFloat()),
                CommentSchema::ATTR_BOOL      => v::isString(v::stringToBool()),
                CommentSchema::ATTR_DATE_TIME => v::isString(v::stringToDateTime(DateTime::JSON_API_FORMAT)),
            ],
            [
                CommentSchema::REL_USER => v::toOneRelationship(UserSchema::TYPE, v::stringToInt(v::between(0, 15))),
            ],
            [
                CommentSchema::REL_EMOTIONS => v::toManyRelationship(EmotionSchema::TYPE, v::isArray()),
            ]
        );
        $validator->assert($input);

        $captures = $validator->getCaptures();
        $this->assertEmpty($validator->getErrors());
        $this->assertCount(9, $captures);
        $this->assertSame(CommentSchema::TYPE, $captures[CommentSchema::RESOURCE_TYPE]);
        $this->assertNull($captures[CommentSchema::RESOURCE_ID]);
        $this->assertSame($text, $captures[CommentSchema::ATTR_TEXT]);
        $this->assertSame($int, $captures[CommentSchema::ATTR_INT]);
        $this->assertSame($float, $captures[CommentSchema::ATTR_FLOAT]);
        $this->assertSame($bool, $captures[CommentSchema::ATTR_BOOL]);
        $this->assertTrue($captures[CommentSchema::ATTR_DATE_TIME] instanceof DateTimeInterface);
        $this->assertSame(9, $captures[CommentSchema::REL_USER]);
        // unlike user relationship we didn't converted strings to int.
        $this->assertSame(['5', '12'], $captures[CommentSchema::REL_EMOTIONS]);
    }

    /**
     * Validation test.
     *
     * @throws Exception
     */
    public function testCaptureValidToOneData(): void
    {
        $jsonInput = <<<EOT
        {
             "data" : { "type" : "users", "id" : "9" }
        }
EOT;
        $input = json_decode($jsonInput, true);

        $container = $this->createContainer();
        $validator = $this->createParser(
            $container,
            'some_rule_name',
            v::success(),
            v::success(),
            [],
            [
                CommentSchema::REL_USER => v::toOneRelationship(UserSchema::TYPE, v::stringToInt(v::between(0, 15))),
            ],
            []
        );
        $validator->assertRelationship('some_id', CommentSchema::REL_USER, $input);

        $captures = $validator->getCaptures();
        $this->assertEmpty($validator->getErrors());
        $this->assertCount(1, $captures);
        $this->assertSame(9, $captures[CommentSchema::REL_USER]);
    }

    /**
     * Validation test.
     *
     * @throws Exception
     */
    public function testCaptureValidToManyData(): void
    {
        $jsonInput = <<<EOT
        {
             "data" : [
                  { "type": "emotions", "id":"5" },
                  { "type": "emotions", "id":"12" }
              ]
        }
EOT;
        $input = json_decode($jsonInput, true);

        $container = $this->createContainer();
        $validator = $this->createParser(
            $container,
            'some_rule_name',
            v::success(),
            v::success(),
            [],
            [],
            [
                CommentSchema::REL_EMOTIONS => v::toManyRelationship(EmotionSchema::TYPE, v::isArray()),
            ]
        );
        $validator->assertRelationship('some_id', CommentSchema::REL_EMOTIONS, $input);

        $captures = $validator->getCaptures();
        $this->assertEmpty($validator->getErrors());
        $this->assertCount(1, $captures);
        // note we didn't converted strings to int.
        $this->assertSame(['5', '12'], $captures[CommentSchema::REL_EMOTIONS]);
    }

    /**
     * Validation test.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testParseNonExistingRelationship(): void
    {
        $jsonInput = <<<EOT
        {
             "data" : { "type": "abc", "id":"123" }
        }
EOT;
        $input = json_decode($jsonInput, true);

        $container = $this->createContainer();
        $validator = $this->createParser(
            $container,
            'some_rule_name',
            v::success(),
            v::success(),
            [],
            [],
            []
        );
        $validator->assertRelationship('some_id', CommentSchema::REL_EMOTIONS, $input);
    }

    /**
     * Validation test.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testCaptureInvalidToManyData(): void
    {
        $jsonInput = <<<EOT
        {
             "data" : [ { "type" : "emotions", "id" : "1" } ]
        }
EOT;
        $input = json_decode($jsonInput, true);

        $container = $this->createContainer();
        $validator = $this->createParser(
            $container,
            'some_rule_name',
            v::success(),
            v::success(),
            [],
            [],
            [
                CommentSchema::REL_EMOTIONS => v::fail(), // <-- any input will fail validation
            ]
        );
        $validator->assertRelationship('some_id', CommentSchema::REL_EMOTIONS, $input);

        $validator->getCaptures();
    }

    /**
     * Validation test.
     *
     * @throws Exception
     */
    public function testCaptureNullOrEmptyInRelationships(): void
    {
        $text      = 'Outside every fat man there was an even fatter man trying to close in';
        $jsonInput = <<<EOT
        {
            "data" : {
                "type"  : "comments",
                "attributes" : {
                    "text-attribute"  : "$text"
                },
                "relationships" : {
                    "user-relationship" : {
                        "data" : null
                    },
                    "emotions-relationship" : {
                        "data" : []
                    }
                }
            }
        }
EOT;
        $input     = json_decode($jsonInput, true);

        $container = $this->createContainer();
        $validator = $this->createParser(
            $container,
            'some_rule_name',
            v::equals(null),
            v::equals('comments'),
            [
                CommentSchema::ATTR_TEXT => v::isString(v::stringLengthMin(1)),
            ],
            [
                CommentSchema::REL_USER => v::toOneRelationship(
                    UserSchema::TYPE,
                    v::nullable(v::stringToInt(v::between(0, 15)))
                ),
            ],
            [
                CommentSchema::REL_EMOTIONS => v::toManyRelationship(EmotionSchema::TYPE, v::isArray()),
            ]
        );
        $validator->assert($input);

        $captures = $validator->getCaptures();
        $this->assertCount(4, $captures);
        $this->assertSame($text, $captures[CommentSchema::ATTR_TEXT]);
        $this->assertSame([], $captures[CommentSchema::REL_EMOTIONS]);
        $this->assertSame(null, $captures[CommentSchema::REL_USER]);
    }

    /**
     * Validation test.
     *
     * @throws Exception
     */
    public function testCaptureInvalidData1(): void
    {
        $text      = 'Outside every fat man there was an even fatter man trying to close in';
        $jsonInput = <<<EOT
        {
            "data" : {
                "type"  : "comments",
                "id"    : null,
                "attributes" : {
                    "text-attribute"  : "$text"
                },
                "relationships" : {
                    "user-relationship" : {
                        "data" : { "type" : "users", "id" : "9" }
                    },
                    "emotions-relationship" : {
                        "data" : [
                            { "type": "emotionsXXX", "id":"1" },
                            { "type": "emotions", "id":"12" }
                        ]
                    }
                }
            }
        }
EOT;
        $input     = json_decode($jsonInput, true);

        $container = $this->createContainer();
        $validator = $this->createParser(
            $container,
            'some_rule_name',
            v::equals(null),
            v::equals('comments'),
            [
                CommentSchema::ATTR_TEXT => v::isString(v::stringLengthBetween(1, 5)),
            ],
            [
                CommentSchema::REL_USER => v::toOneRelationship(
                    UserSchema::TYPE,
                    v::nullable(v::stringToInt(v::between(0, 5)))
                ),
            ],
            [
                CommentSchema::REL_EMOTIONS => v::toManyRelationship(EmotionSchema::TYPE, v::isArray()),
            ]
        );

        $exception    = null;
        $gotException = false;
        try {
            $validator->assert($input);
        } catch (JsonApiException $exception) {
            $gotException = true;
        }
        $this->assertTrue($gotException);

        $this->assertEquals(400, $exception->getHttpCode());

        /** @var Error[] $errors */
        $errors = $exception->getErrors();
        /** @noinspection PhpParamsInspection */
        $this->assertCount(3, $errors);

        $this->assertEquals(422, $errors[0]->getStatus());
        $this->assertEquals('/data/attributes/text-attribute', $errors[0]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals(
            'The value should be between 1 and 5 characters.',
            $errors[0]->getDetail()
        );

        $this->assertEquals(409, $errors[1]->getStatus());
        $this->assertEquals('/data/relationships/user-relationship', $errors[1]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('The value should be between 0 and 5.', $errors[1]->getDetail());

        $this->assertEquals(409, $errors[2]->getStatus());
        $this->assertEquals(
            '/data/relationships/emotions-relationship',
            $errors[2]->getSource()[Error::SOURCE_POINTER]
        );
        $this->assertEquals('The value should be a valid JSON API relationship type.', $errors[2]->getDetail());
    }

    /**
     * Validation test.
     *
     * @throws Exception
     */
    public function testCaptureInvalidData2(): void
    {
        $input = json_decode('{}', true);

        $container = $this->createContainer();
        $validator = $this->createParser(
            $container,
            'some_rule_name',
            v::required(v::equals(null)),
            v::required(v::equals('comments')),
            [
                CommentSchema::ATTR_TEXT => v::required(v::isString(v::stringLengthBetween(1, 5))),
            ],
            [
                CommentSchema::REL_USER => v::toOneRelationship(
                    UserSchema::TYPE,
                    v::required(v::nullable(v::stringToInt(v::between(0, 5))))
                ),
            ],
            [
                CommentSchema::REL_EMOTIONS => v::toManyRelationship(EmotionSchema::TYPE, v::required(v::isArray())),
            ]
        );

        $exception    = null;
        $gotException = false;
        try {
            $validator->assert($input);
        } catch (JsonApiException $exception) {
            $gotException = true;
        }
        $this->assertTrue($gotException);

        $this->assertEquals(400, $exception->getHttpCode());

        /** @var Error[] $errors */
        $errors = $exception->getErrors()->getArrayCopy();
        /** @noinspection PhpParamsInspection */
        $this->assertCount(6, $errors);

        $this->assertEquals(422, $errors[0]->getStatus());
        $this->assertEquals('/data/type', $errors[0]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('JSON API type should be specified.', $errors[0]->getDetail());

        $this->assertEquals(409, $errors[1]->getStatus());
        $this->assertEquals('/data/type', $errors[1]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('The value is required.', $errors[1]->getDetail());

        $this->assertEquals(409, $errors[2]->getStatus());
        $this->assertEquals('/data/id', $errors[2]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('The value is required.', $errors[2]->getDetail());

        $this->assertEquals(422, $errors[3]->getStatus());
        $this->assertEquals('/data/attributes/text-attribute', $errors[3]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('The value is required.', $errors[3]->getDetail());

        $this->assertEquals(409, $errors[4]->getStatus());
        $this->assertEquals('/data/relationships/user-relationship', $errors[4]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('The value is required.', $errors[4]->getDetail());

        $this->assertEquals(409, $errors[5]->getStatus());
        $this->assertEquals(
            '/data/relationships/emotions-relationship',
            $errors[5]->getSource()[Error::SOURCE_POINTER]
        );
        $this->assertEquals('The value is required.', $errors[5]->getDetail());
    }

    /**
     * Validation test.
     *
     * @throws Exception
     */
    public function testCaptureInvalidData3(): void
    {
        $jsonInput = <<<EOT
        {
            "data" : {
                "type"  : "comments",
                "id"    : null,
                "attributes" : null,
                "relationships" : {
                    "user-relationship" : {
                        "data" : null
                    },
                    "emotions-relationship" : {
                        "data" : null
                    }
                }
            }
        }
EOT;
        $input     = json_decode($jsonInput, true);

        $container = $this->createContainer();
        $validator = $this->createParser(
            $container,
            'some_rule_name',
            v::equals(null),
            v::equals('comments'),
            [
                CommentSchema::ATTR_TEXT => v::isString(v::stringLengthBetween(1, 5)),
            ],
            [
                CommentSchema::REL_USER => v::toOneRelationship(UserSchema::TYPE, v::stringToInt(v::between(0, 5))),
            ],
            [
                CommentSchema::REL_EMOTIONS => v::toManyRelationship(EmotionSchema::TYPE, v::isArray()),
            ]
        );

        $exception    = null;
        $gotException = false;
        try {
            $validator->assert($input);
        } catch (JsonApiException $exception) {
            $gotException = true;
        }
        $this->assertTrue($gotException);

        $this->assertEquals(400, $exception->getHttpCode());

        /** @var Error[] $errors */
        $errors = $exception->getErrors()->getArrayCopy();
        /** @noinspection PhpParamsInspection */
        $this->assertCount(3, $errors);

        $this->assertEquals(422, $errors[0]->getStatus());
        $this->assertEquals('/data/attributes', $errors[0]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('JSON API attributes are invalid.', $errors[0]->getDetail());

        $this->assertEquals(422, $errors[1]->getStatus());
        $this->assertEquals(
            '/data/relationships/emotions-relationship',
            $errors[1]->getSource()[Error::SOURCE_POINTER]
        );
        $this->assertEquals('Invalid JSON API relationship.', $errors[1]->getDetail());

        $this->assertEquals(409, $errors[2]->getStatus());
        $this->assertEquals(
            '/data/relationships/user-relationship',
            $errors[2]->getSource()[Error::SOURCE_POINTER]
        );
        $this->assertEquals('The value should be an integer.', $errors[2]->getDetail());
    }

    /**
     * Validation test.
     *
     * @throws Exception
     */
    public function testCaptureInvalidData4(): void
    {
        $jsonInput = <<<EOT
        {
            "data" : {
                "type"  : "comments",
                "id"    : null,
                "attributes" : null,
                "relationships" : {
                    "user-relationship" : null,
                    "emotions-relationship" : null
                }
            }
        }
EOT;
        $input     = json_decode($jsonInput, true);

        $container = $this->createContainer();
        $validator = $this->createParser(
            $container,
            'some_rule_name',
            v::equals(null),
            v::equals('comments'),
            [
                CommentSchema::ATTR_TEXT => v::isString(v::stringLengthBetween(1, 5)),
            ],
            [
                CommentSchema::REL_USER => v::toOneRelationship(UserSchema::TYPE, v::stringToInt(v::between(0, 5))),
            ],
            [
                CommentSchema::REL_EMOTIONS => v::toManyRelationship(EmotionSchema::TYPE, v::isArray()),
            ]
        );

        $exception    = null;
        $gotException = false;
        try {
            $validator->assert($input);
        } catch (JsonApiException $exception) {
            $gotException = true;
        }
        $this->assertTrue($gotException);

        $this->assertEquals(422, $exception->getHttpCode());

        /** @var Error[] $errors */
        $errors = $exception->getErrors()->getArrayCopy();
        /** @noinspection PhpParamsInspection */
        $this->assertCount(3, $errors);

        $this->assertEquals(422, $errors[0]->getStatus());
        $this->assertEquals('/data/attributes', $errors[0]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('JSON API attributes are invalid.', $errors[0]->getDetail());

        $this->assertEquals(422, $errors[1]->getStatus());
        $this->assertEquals(
            '/data/relationships/user-relationship',
            $errors[1]->getSource()[Error::SOURCE_POINTER]
        );
        $this->assertEquals('Invalid JSON API relationship.', $errors[1]->getDetail());

        $this->assertEquals(422, $errors[2]->getStatus());
        $this->assertEquals(
            '/data/relationships/emotions-relationship',
            $errors[2]->getSource()[Error::SOURCE_POINTER]
        );
        $this->assertEquals('Invalid JSON API relationship.', $errors[2]->getDetail());
    }

    /**
     * Validation test.
     *
     * @throws Exception
     */
    public function testValidateWithNotExistingNames(): void
    {
        $text      = 'Outside every fat man there was an even fatter man trying to close in';
        $jsonInput = <<<EOT
        {
            "data" : {
                "type"  : "comments",
                "id"    : null,
                "attributes" : {
                    "text-attribute" : "$text"
                },
                "relationships" : {
                    "user-relationship" : {
                        "data" : { "type" : "users", "id" : "9" }
                    },
                    "emotions-relationship" : {
                        "data" : [
                            { "type": "emotions", "id":"5" },
                            { "type": "emotions", "id":"12" }
                        ]
                    }
                }
            }
        }
EOT;
        $input     = json_decode($jsonInput, true);

        $container = $this->createContainer();
        $validator = $this->createParser(
            $container,
            'some_rule_name',
            v::equals(null),
            v::equals('comments'),
            [],
            [],
            []
        );

        $this->assertFalse($validator->parse($input));

        $this->assertEquals([
            DocumentInterface::KEYWORD_TYPE => CommentSchema::TYPE,
            DocumentInterface::KEYWORD_ID   => null,
        ], $validator->getCaptures());

        $this->assertCount(3, $validator->getErrors());
    }

    /**
     * Validation test.
     *
     * @throws Exception
     */
    public function testInvalidInputDataFormat1(): void
    {
        $jsonInput = <<<EOT
        {
            "data" : {
                "type"  : "comments",
                "id"    : null,
                "attributes" : {
                },
                "relationships" : "oops"
            }
        }
EOT;
        $input     = json_decode($jsonInput, true);

        $container = $this->createContainer();
        $validator = $this->createParser(
            $container,
            'some_rule_name',
            v::equals(null),
            v::equals('comments'),
            [],
            [
                CommentSchema::REL_USER => v::toOneRelationship(UserSchema::TYPE, v::stringToInt(v::between(0, 15))),
            ],
            [
                CommentSchema::REL_EMOTIONS => v::toManyRelationship(EmotionSchema::TYPE, v::isArray()),
            ]
        );
        $this->assertFalse($validator->parse($input));

        $this->assertEquals([
            DocumentInterface::KEYWORD_TYPE => CommentSchema::TYPE,
            DocumentInterface::KEYWORD_ID   => null,
        ], $validator->getCaptures());

        $this->assertCount(1, $validator->getErrors());
    }

    /**
     * Validation test.
     *
     * @throws Exception
     */
    public function testInvalidInputDataFormat2(): void
    {
        $jsonInput = <<<EOT
        {
            "data" : {
                "type"  : "comments",
                "id"    : null,
                "attributes" : {
                },
                "relationships" : {
                    "user-relationship" : {
                        "data" : "oops"
                    },
                    "emotions-relationship" : {
                        "data" : [
                            "oops"
                        ]
                    }
                }
            }
        }
EOT;
        $input     = json_decode($jsonInput, true);

        $container = $this->createContainer();
        $validator = $this->createParser(
            $container,
            'some_rule_name',
            v::equals(null),
            v::equals('comments'),
            [],
            [
                CommentSchema::REL_USER => v::toOneRelationship(UserSchema::TYPE, v::stringToInt(v::between(0, 15))),
            ],
            [
                CommentSchema::REL_EMOTIONS => v::toManyRelationship(EmotionSchema::TYPE, v::isArray()),
            ]
        );
        $this->assertFalse($validator->parse($input));

        $this->assertEquals([
            DocumentInterface::KEYWORD_TYPE => CommentSchema::TYPE,
            DocumentInterface::KEYWORD_ID   => null,
        ], $validator->getCaptures());

        $this->assertCount(2, $validator->getErrors());
    }

    /**
     * Test unique in database rule.
     *
     * @throws Exception
     * @throws DBALException
     */
    public function testDbUniqueRule(): void
    {
        $container                    = $this->createContainer();
        $container[Connection::class] = $connection = $this->createConnection();
        $this->migrateDatabase($connection);

        $validator = $this->createParser(
            $container,
            'some_rule_name',
            v::unique(Comment::TABLE_NAME, Comment::FIELD_ID),
            v::equals('comments'),
            [],
            [],
            []
        );

        $jsonInput = <<<EOT
        {
            "data" : {
                "type"  : "comments",
                "id"    : "12345",
                "attributes" : []
            }
        }
EOT;
        $input     = json_decode($jsonInput, true);
        $this->assertTrue($validator->parse($input));

        $jsonInput = <<<EOT
        {
            "data" : {
                "type"  : "comments",
                "id"    : "1",
                "attributes" : []
            }
        }
EOT;
        $input     = json_decode($jsonInput, true);
        $this->assertFalse($validator->parse($input));
    }

    /**
     * Test cover.
     *
     * @throws Exception
     */
    public function testCoverEnableIgnoreUnknowns(): void
    {
        $validator = $this->createParser(
            $this->createContainer(),
            'some_rule_name',
            v::unique(Comment::TABLE_NAME, Comment::FIELD_ID),
            v::equals('comments'),
            [],
            [],
            []
        );

        $method = new ReflectionMethod(DataParser::class, 'enableIgnoreUnknowns');
        $method->setAccessible(true);
        $method->invoke($validator);

        // if the method exists the test is OK
        $this->assertTrue(true);
    }

    /**
     * Add some test coverage for rarely used functionality.
     *
     * @throws Exception
     */
    public function testAddTestCoverToExecuteEnds(): void
    {
        $passThroughRule = new class extends BaseRule
        {
            /**
             * @inheritdoc
             */
            public function toBlock(): ExecutionBlockInterface
            {
                $startOrEndCallable = [DataParserTest::class, 'successValidationStartOrEnd'];
                $passThrough = (new ProcedureBlock([Success::class, 'execute']))
                    ->setStartCallable($startOrEndCallable)
                    ->setProperties($this->composeStandardProperties($this->getName(), false))
                    ->setEndCallable($startOrEndCallable);

                return $passThrough;
            }
        };

        $jsonInput = <<<EOT
        {
            "data" : {
                "type"  : "comments",
                "id"    : null
            }
        }
EOT;
        $input = json_decode($jsonInput, true);

        $container = $this->createContainer();
        $validator = $this->createParser($container, 'some_rule_name', $passThroughRule, v::success(), [], [], []);
        $validator->assert($input);

        $captures = $validator->getCaptures();
        $this->assertEmpty($validator->getErrors());
        $this->assertCount(1, $captures);
        $this->assertSame('comments', $captures['type']);
    }

    /**
     * A dummy rule for validation testing.
     *
     * @param ContextInterface $context
     *
     * @return array
     */
    public static function successValidationStartOrEnd(ContextInterface $context): array
    {
        assert($context);

        return [];
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param ContainerInterface $container
     * @param string             $rulesClass
     * @param RuleInterface      $idRule
     * @param RuleInterface      $typeRule
     * @param array              $attributeRules
     * @param array              $toOneRules
     * @param array              $toManyRules
     *
     * @return JsonApiDataParserInterface
     */
    private function createParser(
        ContainerInterface $container,
        string $rulesClass,
        RuleInterface $idRule,
        RuleInterface $typeRule,
        array $attributeRules,
        array $toOneRules,
        array $toManyRules
    ): JsonApiDataParserInterface {
        $serializedData = (new JsonApiDataRulesSerializer(new BlockSerializer()))->addDataRules(
            $rulesClass,
            $idRule,
            $typeRule,
            $attributeRules,
            $toOneRules,
            $toManyRules
        )->getData();

        $exception = null;
        $parser    = null;
        $blocks    = JsonApiQueryRulesSerializer::readBlocks($serializedData);
        try {
            /** @var FormatterFactoryInterface $formatterFactory */
            $formatterFactory = $container->get(FormatterFactoryInterface::class);
            $parser = new DataParser(
                $rulesClass,
                JsonApiDataRulesSerializer::class,
                $serializedData,
                new ContextStorage($blocks, $container),
                new JsonApiErrorCollection($formatterFactory->createFormatter(Messages::NAMESPACE_NAME)),
                $container->get(FormatterFactoryInterface::class)
            );
        } catch (Exception | NotFoundExceptionInterface | ContainerExceptionInterface $exception) {
        }
        $this->assertNull($exception);

        return $parser;
    }

    /**
     * @return ContainerInterface
     */
    private function createContainer(): ContainerInterface
    {
        $container = new Container();

        $container[ModelSchemaInfoInterface::class]  = $schemas = $this->getModelSchemas();
        $container[JsonSchemasInterface::class]      = $this->getJsonSchemas(new Factory($container), $schemas);
        $container[FormatterFactoryInterface::class] = new FormatterFactory();

        return $container;
    }
}
