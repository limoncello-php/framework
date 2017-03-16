<?php namespace Limoncello\Tests\Validation;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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

use Limoncello\Tests\Validation\Data\AppValidator as v;
use Limoncello\Validation\Captures\CaptureAggregator;
use Limoncello\Validation\Contracts\CaptureAggregatorInterface;
use Limoncello\Validation\Contracts\MessageCodes;
use Limoncello\Validation\Errors\Error;
use Limoncello\Validation\Validator;

/**
 * @package Limoncello\Tests\Validation
 */
class CapturesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CaptureAggregatorInterface
     */
    private $aggregator;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->aggregator = new CaptureAggregator();
    }

    /**
     * Capture test.
     */
    public function testSuccessfulSingleCapture()
    {
        $modelFieldName = 'id_some_field';
        $idRule         = v::notNull();

        $identityCapture = v::singleCapture($modelFieldName, $idRule, $this->aggregator);

        // I think setting parameter name should effect error reporting but not data capture. Let's check it.
        $paramName = 'some-param-name';
        $identityCapture->setParameterName($paramName);

        $rules = v::arrayX([
            'id'   => $identityCapture,
            'type' => v::equals('comments'),
        ]);

        $input = [
            'id'   => '123',
            'type' => 'comments',
        ];

        $errors = $this->readErrors(v::validator($rules), $input);
        $this->assertEmpty($errors);

        $this->assertEquals($paramName, $identityCapture->getParameterName());

        $this->assertEquals([
            $modelFieldName => '123',
        ], $this->aggregator->getCaptures());

        $this->assertTrue($identityCapture->isStateless());
    }

    /**
     * Capture test.
     */
    public function testFailedSingleCapture()
    {
        $modelFieldName = 'id_some_field';
        $idRule         = v::isInt();

        $identityCapture = v::singleCapture($modelFieldName, $idRule, $this->aggregator);

        // I think setting parameter name should effect error reporting but not data capture. Let's check it.
        $paramName = 'some-param-name';
        $identityCapture->setParameterName($paramName);

        $rules = v::arrayX([
            'id'   => $identityCapture,
            'type' => v::equals('comments'),
        ]);

        $input = [
            'id'   => 'abc-id',
            'type' => 'comments',
        ];

        $errors = $this->readErrors(v::validator($rules), $input);
        $this->assertCount(1, $errors);
        $this->assertEquals($paramName, $errors[0]->getParameterName());
        $this->assertEquals('abc-id', $errors[0]->getParameterValue());
        $this->assertEquals(MessageCodes::IS_INT, $errors[0]->getMessageCode());

        $this->assertEquals($paramName, $identityCapture->getParameterName());

        $this->assertEquals([], $this->aggregator->getCaptures());
    }

    /**
     * Capture test.
     */
    public function testSuccessfulMultipleCapture()
    {
        $modelFieldName = 'id_some_field';
        $idRule         = v::notNull();

        $identitiesCapture = v::multiCapture($modelFieldName, $idRule, $this->aggregator);

        // I think setting parameter name should effect error reporting but not data capture. Let's check it.
        $paramName = 'some-param-name';
        $identitiesCapture->setParameterName($paramName);

        $rules = v::eachX(v::arrayX([
            'id'   => $identitiesCapture,
            'type' => v::equals('comments'),
        ]));

        $input = [
            [
                'id'   => '1',
                'type' => 'comments',
            ],
            [
                'id'   => '2',
                'type' => 'comments',
            ],
            [
                'id'   => '3',
                'type' => 'comments',
            ],
        ];

        $errors = $this->readErrors(v::validator($rules), $input);
        $this->assertEmpty($errors);

        $this->assertEquals($paramName, $identitiesCapture->getParameterName());

        $this->assertEquals([
            $modelFieldName => ['1', '2', '3'],
        ], $this->aggregator->getCaptures());
    }

    /**
     * Advanced validate & capture test for JSON API.
     */
    public function testValidateAndCaptureJsonApi()
    {
        $title = 'JSON API paints my bikeshed!';
        $body  = 'Outside every fat man there was an even fatter man trying to close in';
        $jsonInput = <<<EOT
        {
            "data" : {
                "type"  : "posts",
                "id"    : null,
                "attributes" : {
                    "title" : "$title",
                    "body"  : "$body"
                },
                "relationships" : {
                    "author" : {
                        "data" : { "type" : "people", "id" : "9" }
                    },
                    "tags" : {
                        "data" : [
                            { "type": "tags", "id":"5" },
                            { "type": "tags", "id":"12" }
                        ]
                    }
                }
            }
        }
EOT;
        $input = json_decode($jsonInput, true);

        $values = new CaptureAggregator();

        $attributeRules = [
            'title' => v::required(v::stringLength(1, 255)),
            'body'  => v::required(v::stringLength(1)),
        ];
        $isExistingAuthorId = v::isNumeric();
        $toOneRules = [
            'author' => ['people', $isExistingAuthorId],
        ];
        $isExistingTagId = v::isNumeric();
        $toManyRules = [
            'tags' => ['tags', $isExistingTagId],
        ];

        $rules = v::isJsonApi($values, 'posts', v::isNull(), $attributeRules, $toOneRules, $toManyRules);

        $errors = $this->readErrors(v::validator($rules), $input);
        $this->assertEmpty($errors);
        $captures = $values->getCaptures();
        $this->assertEquals(null, $captures['id']);
        $this->assertEquals($title, $captures['title']);
        $this->assertEquals($body, $captures['body']);
        $this->assertEquals('9', $captures['author']);
        $this->assertEquals(['5', '12'], $captures['tags']);
    }

    /**
     * If data were not given in input nothing should be added to capture aggregator.
     */
    public function testNoDataForCapture()
    {
        $idRule = v::notNull();
        $rules = v::arrayX([
            'f1' => v::singleCapture('f1-c', $idRule, $this->aggregator),
            'f2' => v::singleCapture('f2-c', $idRule, $this->aggregator),
            'f3' => v::singleCapture('f3-c', $idRule, $this->aggregator),
        ]);

        $input = [
            'f1' => 'value1',
            'f3' => 'value3',
        ];

        $errors = $this->readErrors(v::validator($rules), $input);
        $this->assertEmpty($errors);

        $this->assertEquals([
            'f1-c' => 'value1',
            'f3-c' => 'value3',
        ], $this->aggregator->getCaptures());
    }

    /**
     * @param v     $validator
     * @param mixed $input
     *
     * @return Error[]
     */
    private function readErrors(v $validator, $input)
    {
        $errors = [];
        foreach ($validator->validate($input) as $error) {
            /** @var Error $error */
            $errors[] = $error;
        }

        return $errors;
    }
}
