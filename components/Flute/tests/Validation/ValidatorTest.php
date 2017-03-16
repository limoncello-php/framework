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

use Limoncello\Flute\I18n\Translator as JsonApiTranslator;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Schemes\CommentSchema;
use Limoncello\Tests\Flute\Data\Validation\AppValidator;
use Limoncello\Tests\Flute\TestCase;
use Limoncello\Validation\I18n\Locales\EnUsLocale;
use Limoncello\Validation\I18n\Translator;
use Limoncello\Validation\Validator as v;
use Neomerx\JsonApi\Document\Error;
use Neomerx\JsonApi\Exceptions\JsonApiException;

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
        $jsonSchemes = $this->getJsonSchemes($this->getModelSchemes());
        $validator   = $this->createValidator($jsonSchemes);

        $text  = 'Outside every fat man there was an even fatter man trying to close in';
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
        $input = json_decode($jsonInput, true);

        $idRule = v::isNull();
        $attributeRules = [
            CommentSchema::ATTR_TEXT => v::andX(v::isString(), v::stringLength(1)),
        ];
        $toOneRules = [
            CommentSchema::REL_USER => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(15))),
        ];
        $toManyRules = [
            CommentSchema::REL_EMOTIONS => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(15))),
        ];

        $schema = $jsonSchemes->getSchemaByType(Comment::class);
        list ($index, $attrCaptures, $toManyCaptures) =
            $validator->assert($schema, $input, $idRule, $attributeRules, $toOneRules, $toManyRules);

        $this->assertCount(2, $attrCaptures);
        $this->assertCount(1, $toManyCaptures);
        $this->assertNull($index);
        $this->assertEquals($text, $attrCaptures[Comment::FIELD_TEXT]);
        $this->assertEquals(9, $attrCaptures[Comment::FIELD_ID_USER]);
        $this->assertEquals([5, 12], $toManyCaptures[Comment::REL_EMOTIONS]);
    }
    /**
     * Validation test.
     */
    public function testCaptureNullInTo1Relationship()
    {
        $jsonSchemes = $this->getJsonSchemes($this->getModelSchemes());
        $validator   = $this->createValidator($jsonSchemes);

        $text  = 'Outside every fat man there was an even fatter man trying to close in';
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
        $input = json_decode($jsonInput, true);

        $idRule = v::isNull();
        $attributeRules = [
            CommentSchema::ATTR_TEXT => v::andX(v::isString(), v::stringLength(1)),
        ];
        $toOneRules = [
            CommentSchema::REL_USER => v::nullable(v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(15)))),
        ];

        $schema = $jsonSchemes->getSchemaByType(Comment::class);
        list ($index, $attrCaptures) = $validator->assert($schema, $input, $idRule, $attributeRules, $toOneRules);

        $this->assertNull($index);
        $this->assertCount(2, $attrCaptures);
        $this->assertEquals($text, $attrCaptures[Comment::FIELD_TEXT]);
        $this->assertEquals(null, $attrCaptures[Comment::FIELD_ID_USER]);
    }

    /**
     * Validation test.
     */
    public function testCaptureInvalidData1()
    {
        $jsonSchemes = $this->getJsonSchemes($this->getModelSchemes());
        $validator   = $this->createValidator($jsonSchemes);

        $text  = 'Outside every fat man there was an even fatter man trying to close in';
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
        $input = json_decode($jsonInput, true);

        $idRule = v::isNull();
        $attributeRules = [
            CommentSchema::ATTR_TEXT => v::andX(v::isString(), v::stringLength(1, 5)),
        ];
        $toOneRules = [
            CommentSchema::REL_USER => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];
        $toManyRules = [
            CommentSchema::REL_EMOTIONS => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];

        $exception    = null;
        $gotException = false;
        $schema       = $jsonSchemes->getSchemaByType(Comment::class);
        try {
            $validator->assert($schema, $input, $idRule, $attributeRules, $toOneRules, $toManyRules);
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
        $jsonSchemes = $this->getJsonSchemes($this->getModelSchemes());
        $validator   = $this->createValidator($jsonSchemes);

        $input = json_decode('{}', true);

        $idRule = v::required(v::isNull());
        $attributeRules = [
            CommentSchema::ATTR_TEXT => v::required(v::andX(v::isString(), v::stringLength(1, 5))),
        ];
        $toOneRules = [
            CommentSchema::REL_USER => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];
        $toManyRules = [
            CommentSchema::REL_EMOTIONS => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];

        $exception    = null;
        $gotException = false;
        $schema       = $jsonSchemes->getSchemaByType(Comment::class);
        try {
            $validator->assert($schema, $input, $idRule, $attributeRules, $toOneRules, $toManyRules);
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
        $jsonSchemes = $this->getJsonSchemes($this->getModelSchemes());
        $validator   = $this->createValidator($jsonSchemes);

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
        $input = json_decode($jsonInput, true);

        $idRule = v::isNull();
        $attributeRules = [
            CommentSchema::ATTR_TEXT => v::andX(v::isString(), v::stringLength(1, 5)),
        ];
        $toOneRules = [
            CommentSchema::REL_USER => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];
        $toManyRules = [
            CommentSchema::REL_EMOTIONS => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];

        $exception    = null;
        $gotException = false;
        $schema       = $jsonSchemes->getSchemaByType(Comment::class);
        try {
            $validator->assert($schema, $input, $idRule, $attributeRules, $toOneRules, $toManyRules);
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
        $jsonSchemes = $this->getJsonSchemes($this->getModelSchemes());
        $validator   = $this->createValidator($jsonSchemes);

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
        $input = json_decode($jsonInput, true);

        $idRule = v::isNull();
        $attributeRules = [
            CommentSchema::ATTR_TEXT => v::andX(v::isString(), v::stringLength(1, 5)),
        ];
        $toOneRules = [
            CommentSchema::REL_USER => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];
        $toManyRules = [
            CommentSchema::REL_EMOTIONS => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];

        $exception    = null;
        $gotException = false;
        $schema       = $jsonSchemes->getSchemaByType(Comment::class);
        try {
            $validator->assert($schema, $input, $idRule, $attributeRules, $toOneRules, $toManyRules);
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
        $jsonSchemes = $this->getJsonSchemes($this->getModelSchemes());
        $validator   = $this->createValidator($jsonSchemes);

        $input = json_decode('{}', true);

        $idRule = v::isNull();
        $attributeRules = [
            CommentSchema::ATTR_TEXT => v::required(v::andX(v::isString(), v::stringLength(1, 5))),
        ];
        $toOneRules = [
            CommentSchema::REL_USER => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];
        $toManyRules = [
            CommentSchema::REL_EMOTIONS => v::andX(v::isNumeric(), v::andX(v::moreThan(0), v::lessThan(2))),
        ];

        $exception    = null;
        $gotException = false;
        $schema       = $jsonSchemes->getSchemaByType(Comment::class);
        try {
            $validator->assert($schema, $input, $idRule, $attributeRules, $toOneRules, $toManyRules);
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
     * @param $jsonSchemes
     *
     * @return AppValidator
     */
    private function createValidator($jsonSchemes)
    {
        $jsonApiTranslator    = new JsonApiTranslator();
        $validationTranslator = new Translator(EnUsLocale::getLocaleCode(), EnUsLocale::getMessages());

        $validator = new AppValidator(
            $jsonApiTranslator,
            $validationTranslator,
            $jsonSchemes,
            $this->getModelSchemes(),
            $this->createConnection()
        );

        return $validator;
    }
}
