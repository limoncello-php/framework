<?php namespace Limoncello\Tests\Flute\Validation;

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

use Limoncello\Container\Container;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Flute\Contracts\I18n\TranslatorInterface as JsonApiTranslatorInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Contracts\Validation\ValidatorInterface;
use Limoncello\Flute\Factory;
use Limoncello\Flute\I18n\Translator as JsonApiTranslator;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Schemes\CommentSchema;
use Limoncello\Tests\Flute\Data\Validation\AppValidator;
use Limoncello\Tests\Flute\TestCase;
use Limoncello\Validation\Contracts\TranslatorInterface as ValidationTranslatorInterface;
use Limoncello\Validation\I18n\Locales\EnUsLocale;
use Limoncello\Validation\I18n\Translator;
use Limoncello\Validation\Validator as v;
use Neomerx\JsonApi\Document\Error;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class ValidatorTest extends TestCase
{
    /**
     * Validation test.
     */
    public function testCaptureValidData()
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
                            { "type": "emotions", "id":"5" },
                            { "type": "emotions", "id":"12" }
                        ]
                    }
                }
            }
        }
EOT;
        $input     = json_decode($jsonInput, true);
        $rules     = [
            AppValidator::RULE_INDEX      => v::isNull(),
            AppValidator::RULE_ATTRIBUTES => [
                CommentSchema::ATTR_TEXT => v::andX(v::isString(), v::stringLength(1)),
            ],
            AppValidator::RULE_TO_ONE     => [
                CommentSchema::REL_USER => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(15))),
            ],
            AppValidator::RULE_TO_MANY    => [
                CommentSchema::REL_EMOTIONS => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(15))),
            ],
        ];

        $container = $this->createContainer();
        $validator = $this->createValidator($container, CommentSchema::TYPE, $rules);

        $validator->assert($input);

        $captures = $validator->getCaptures();
        $this->assertCount(4, $captures);
        $this->assertNull($captures[Comment::FIELD_ID]);
        $this->assertEquals($text, $captures[Comment::FIELD_TEXT]);
        $this->assertEquals(9, $captures[Comment::FIELD_ID_USER]);
        $this->assertEquals([5, 12], $captures[Comment::REL_EMOTIONS]);
    }

    /**
     * Validation test.
     */
    public function testCaptureNullInTo1Relationship()
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
                    }
                }
            }
        }
EOT;
        $input     = json_decode($jsonInput, true);

        $rules = [
            AppValidator::RULE_INDEX      => v::isNull(),
            AppValidator::RULE_ATTRIBUTES => [
                CommentSchema::ATTR_TEXT => v::andX(v::isString(), v::stringLength(1)),
            ],
            AppValidator::RULE_TO_ONE     => [
                CommentSchema::REL_USER =>
                    v::nullable(v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(15)))),
            ],
        ];

        $container = $this->createContainer();
        $validator = $this->createValidator($container, CommentSchema::TYPE, $rules);

        $validator->assert($input);

        $captures = $validator->getCaptures();
        $this->assertCount(2, $captures);
        $this->assertEquals($text, $captures[Comment::FIELD_TEXT]);
        $this->assertEquals(null, $captures[Comment::FIELD_ID_USER]);
    }

    /**
     * Validation test.
     */
    public function testCaptureInvalidData1()
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

        $idRule         = v::isNull();
        $attributeRules = [
            CommentSchema::ATTR_TEXT => v::andX(v::isString(), v::stringLength(1, 5)),
        ];
        $toOneRules     = [
            CommentSchema::REL_USER => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];
        $toManyRules    = [
            CommentSchema::REL_EMOTIONS => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];

        $rules = [
            AppValidator::RULE_INDEX      => $idRule,
            AppValidator::RULE_ATTRIBUTES => $attributeRules,
            AppValidator::RULE_TO_ONE     => $toOneRules,
            AppValidator::RULE_TO_MANY    => $toManyRules,
        ];

        $container = $this->createContainer();
        $validator = $this->createValidator($container, CommentSchema::TYPE, $rules);

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
        $this->assertCount(4, $errors);

        $this->assertEquals(422, $errors[0]->getStatus());
        $this->assertEquals('/data/attributes/text-attribute', $errors[0]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals(
            'The `text-attribute` value should be between 1 and 5 characters.',
            $errors[0]->getDetail()
        );

        $this->assertEquals(422, $errors[1]->getStatus());
        $this->assertEquals('/data/relationships/user-relationship', $errors[1]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('The `user-relationship` value is invalid.', $errors[1]->getDetail());

        $this->assertEquals(422, $errors[2]->getStatus());
        $this->assertEquals(
            '/data/relationships/emotions-relationship',
            $errors[2]->getSource()[Error::SOURCE_POINTER]
        );
        $this->assertEquals('The `emotions-relationship` value is invalid.', $errors[2]->getDetail());

        $this->assertEquals(422, $errors[3]->getStatus());
        $this->assertEquals(
            '/data/relationships/emotions-relationship',
            $errors[3]->getSource()[Error::SOURCE_POINTER]
        );
        $this->assertEquals('The `emotions-relationship` value is invalid.', $errors[3]->getDetail());
    }

    /**
     * Validation test.
     */
    public function testCaptureInvalidData2()
    {
        $input = json_decode('{}', true);

        $idRule         = v::required(v::isNull());
        $attributeRules = [
            CommentSchema::ATTR_TEXT => v::required(v::andX(v::isString(), v::stringLength(1, 5))),
        ];
        $toOneRules     = [
            CommentSchema::REL_USER => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];
        $toManyRules    = [
            CommentSchema::REL_EMOTIONS => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];

        $rules = [
            AppValidator::RULE_INDEX      => $idRule,
            AppValidator::RULE_ATTRIBUTES => $attributeRules,
            AppValidator::RULE_TO_ONE     => $toOneRules,
            AppValidator::RULE_TO_MANY    => $toManyRules,
        ];

        $container = $this->createContainer();
        $validator = $this->createValidator($container, CommentSchema::TYPE, $rules);

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
        $this->assertCount(3, $errors);

        $this->assertEquals(422, $errors[0]->getStatus());
        $this->assertEquals('/data/type', $errors[0]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('The `type` value is required.', $errors[0]->getDetail());

        $this->assertEquals(422, $errors[1]->getStatus());
        $this->assertEquals('/data/id', $errors[1]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('The `id` value is required.', $errors[1]->getDetail());

        $this->assertEquals(422, $errors[2]->getStatus());
        $this->assertEquals('/data/attributes/text-attribute', $errors[2]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('The `text-attribute` value is required.', $errors[2]->getDetail());
    }

    /**
     * Validation test.
     */
    public function testCaptureInvalidData3()
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

        $idRule         = v::isNull();
        $attributeRules = [
            CommentSchema::ATTR_TEXT => v::andX(v::isString(), v::stringLength(1, 5)),
        ];
        $toOneRules     = [
            CommentSchema::REL_USER => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];
        $toManyRules    = [
            CommentSchema::REL_EMOTIONS => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];

        $rules = [
            AppValidator::RULE_INDEX      => $idRule,
            AppValidator::RULE_ATTRIBUTES => $attributeRules,
            AppValidator::RULE_TO_ONE     => $toOneRules,
            AppValidator::RULE_TO_MANY    => $toManyRules,
        ];

        $container = $this->createContainer();
        $validator = $this->createValidator($container, CommentSchema::TYPE, $rules);

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
        $this->assertCount(2, $errors);

        $this->assertEquals(422, $errors[0]->getStatus());
        $this->assertEquals('/data/relationships/user-relationship', $errors[0]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('The `user-relationship` value should be an array.', $errors[0]->getDetail());

        $this->assertEquals(422, $errors[1]->getStatus());
        $this->assertEquals(
            '/data/relationships/emotions-relationship',
            $errors[1]->getSource()[Error::SOURCE_POINTER]
        );
        $this->assertEquals('The `emotions-relationship` value should be an array.', $errors[1]->getDetail());
    }

    /**
     * Validation test.
     */
    public function testCaptureInvalidData4()
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

        $idRule         = v::isNull();
        $attributeRules = [
            CommentSchema::ATTR_TEXT => v::andX(v::isString(), v::stringLength(1, 5)),
        ];
        $toOneRules     = [
            CommentSchema::REL_USER => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];
        $toManyRules    = [
            CommentSchema::REL_EMOTIONS => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];

        $rules = [
            AppValidator::RULE_INDEX      => $idRule,
            AppValidator::RULE_ATTRIBUTES => $attributeRules,
            AppValidator::RULE_TO_ONE     => $toOneRules,
            AppValidator::RULE_TO_MANY    => $toManyRules,
        ];

        $container = $this->createContainer();
        $validator = $this->createValidator($container, CommentSchema::TYPE, $rules);

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
        $this->assertCount(2, $errors);

        $this->assertEquals(422, $errors[0]->getStatus());
        $this->assertEquals('/data/relationships/user-relationship', $errors[0]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('The `user-relationship` value should be an array.', $errors[0]->getDetail());

        $this->assertEquals(422, $errors[1]->getStatus());
        $this->assertEquals(
            '/data/relationships/emotions-relationship',
            $errors[1]->getSource()[Error::SOURCE_POINTER]
        );
        $this->assertEquals('The `emotions-relationship` value should be an array.', $errors[1]->getDetail());
    }

    /**
     * Validation test.
     */
    public function testCaptureInvalidData5()
    {
        $input = json_decode('{}', true);

        $idRule         = v::isNull();
        $attributeRules = [
            CommentSchema::ATTR_TEXT => v::required(v::andX(v::isString(), v::stringLength(1, 5))),
        ];
        $toOneRules     = [
            CommentSchema::REL_USER => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];
        $toManyRules    = [
            CommentSchema::REL_EMOTIONS => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];

        $rules = [
            AppValidator::RULE_INDEX      => $idRule,
            AppValidator::RULE_ATTRIBUTES => $attributeRules,
            AppValidator::RULE_TO_ONE     => $toOneRules,
            AppValidator::RULE_TO_MANY    => $toManyRules,
        ];

        $container = $this->createContainer();
        $validator = $this->createValidator($container, CommentSchema::TYPE, $rules);

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
        $this->assertCount(2, $errors);

        $this->assertEquals(422, $errors[0]->getStatus());
        $this->assertEquals('/data/type', $errors[0]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('The `type` value is required.', $errors[0]->getDetail());

        $this->assertEquals(422, $errors[1]->getStatus());
        $this->assertEquals('/data/attributes/text-attribute', $errors[1]->getSource()[Error::SOURCE_POINTER]);
        $this->assertEquals('The `text-attribute` value is required.', $errors[1]->getDetail());
    }

    /**
     * @param ContainerInterface $container
     * @param string             $jsonType
     * @param array              $rules
     *
     * @return ValidatorInterface
     */
    private function createValidator(
        ContainerInterface $container,
        string $jsonType,
        array $rules
    ): ValidatorInterface {
        $validator = new AppValidator($container, $jsonType, $rules);

        return $validator;
    }

    /**
     * @return ContainerInterface
     */
    private function createContainer(): ContainerInterface
    {
        $container = new Container();

        $container[ModelSchemeInfoInterface::class]      = $schemes = $this->getModelSchemes();
        $container[JsonSchemesInterface::class]          = $this->getJsonSchemes(new Factory($container), $schemes);
        $container[JsonApiTranslatorInterface::class]    = new JsonApiTranslator();
        $container[ValidationTranslatorInterface::class] =
            new Translator(EnUsLocale::getLocaleCode(), EnUsLocale::getMessages());

        return $container;
    }
}
