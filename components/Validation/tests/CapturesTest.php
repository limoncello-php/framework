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

use DateTimeImmutable;
use Limoncello\Tests\Validation\Data\AppValidator as v;
use Limoncello\Validation\Captures\CaptureAggregator;
use Limoncello\Validation\Contracts\CaptureAggregatorInterface;
use Limoncello\Validation\Contracts\MessageCodes;
use Limoncello\Validation\Contracts\ValidatorInterface;
use Limoncello\Validation\Errors\Error;
use stdClass;

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
        $bool1FieldName = 'id_bool_1';
        $bool2FieldName = 'id_bool_2';
        $bool3FieldName = 'id_bool_3';
        $bool4FieldName = 'id_bool_4';
        $bool5FieldName = 'id_bool_5';
        $floatFieldName = 'id_float';
        $dt1FieldName   = 'id_dt_1';
        $dt2FieldName   = 'id_dt_2';

        $identityCapture = v::singleCapture($modelFieldName, v::notNull(), $this->aggregator);
        $bool1Capture    = v::singleCapture($bool1FieldName, v::notNull(), $this->aggregator);
        $bool2Capture    = v::singleCapture($bool2FieldName, v::notNull(), $this->aggregator);
        $bool3Capture    = v::singleCapture($bool3FieldName, v::notNull(), $this->aggregator);
        $bool4Capture    = v::singleCapture($bool4FieldName, v::notNull(), $this->aggregator);
        $bool5Capture    = v::singleCapture($bool5FieldName, v::notNull(), $this->aggregator);
        $floatCapture    = v::singleCapture($floatFieldName, v::notNull(), $this->aggregator);
        $dt1Capture      = v::singleCapture($dt1FieldName, v::notNull(), $this->aggregator);
        $dt2Capture      = v::singleCapture($dt2FieldName, v::notNull(), $this->aggregator);

        // I think setting parameter name should effect error reporting but not data capture. Let's check it.
        $paramName = 'some-param-name';
        $identityCapture->setParameterName($paramName);

        $rules = v::arrayX([
            'id'    => v::toInt($identityCapture),
            'type'  => v::toString(v::equals('comments')),
            'bool1' => v::toBool($bool1Capture),
            'bool2' => v::toBool($bool2Capture),
            'bool3' => v::toBool($bool3Capture),
            'bool4' => v::toBool($bool4Capture),
            'bool5' => v::toBool($bool5Capture),
            'float' => v::toFloat($floatCapture),
            'dt1'   => v::toDateTime($dt1Capture, 'Y-m-d H:i:s'),
            'dt2'   => v::toDateTime($dt2Capture, 'Y-m-d H:i:s'),
        ]);

        $dt1   = new DateTimeImmutable('2000-01-02');
        $dt2   = new DateTimeImmutable('2003-04-05');
        $input = [
            'id'    => '123',
            'type'  => 'comments',
            'bool1' => 'true',
            'bool2' => '1',
            'bool3' => 'false',
            'bool4' => '0',
            'bool5' => true,
            'float' => '1.23',
            'dt1'   => '2000-01-02 00:00:00',
            'dt2'   => $dt2,
        ];

        $errors = $this->readErrors(v::validator($rules), $input);
        $this->assertEmpty($errors);

        $this->assertEquals($paramName, $identityCapture->getParameterName());

        $captures = $this->aggregator->getCaptures();

        // to make sure actual data type transformed we have to use `assertSame` method however
        // this method do not serve our purpose for DateTime. Thus for date time values we would
        // use `assertEquals` method.
        $this->assertEquals($dt1, $captures[$dt1FieldName]);
        $this->assertEquals($dt2, $captures[$dt2FieldName]);
        unset($captures[$dt1FieldName]);
        unset($captures[$dt2FieldName]);

        $this->assertSame([
            $modelFieldName => 123,
            $bool1FieldName => true,
            $bool2FieldName => true,
            $bool3FieldName => false,
            $bool4FieldName => false,
            $bool5FieldName => true,
            $floatFieldName => 1.23
        ], $captures);

        $this->assertTrue($identityCapture->isStateless());
    }

    /**
     * Capture test.
     */
    public function testFailedSingleCapture()
    {
        $modelFieldName = 'id_some_field';
        $boolFieldName  = 'id_bool_1';
        $floatFieldName = 'id_float';
        $dtFieldName    = 'id_dt';
        $intFieldName   = 'id_int';

        $identityCapture = v::singleCapture($modelFieldName, v::isInt(), $this->aggregator);
        $boolCapture     = v::singleCapture($boolFieldName, v::notNull(), $this->aggregator);
        $floatCapture    = v::singleCapture($floatFieldName, v::notNull(), $this->aggregator);
        $dtCapture       = v::singleCapture($dtFieldName, v::notNull(), $this->aggregator);
        $intCapture      = v::singleCapture($intFieldName, v::moreThan(5), $this->aggregator);

        // I think setting parameter name should effect error reporting but not data capture. Let's check it.
        $paramName = 'some-param-name';
        $idRule    = v::toInt($identityCapture)->setParameterName($paramName);

        $rules  = v::arrayX([
            'id'    => $idRule,
            'type'  => v::toString(v::equals('comments')),
            'bool'  => v::toBool($boolCapture),
            'float' => v::toFloat($floatCapture),
            'dt'    => v::toDateTime($dtCapture, 'Y-m-d H:i:s'),
            'int'   => v::toInt($intCapture),
        ]);

        $input = [
            'id'    => 'abc-id',
            'type'  => new stdClass(),
            'bool'  => new stdClass(),
            'float' => new stdClass(),
            'dt'    => new stdClass(),
            'int'   => 3, // error: less than 5
        ];

        $errors = $this->readErrors(v::validator($rules), $input);
        $this->assertCount(6, $errors);
        $this->assertEquals($paramName, $errors[0]->getParameterName());
        $this->assertEquals('abc-id', $errors[0]->getParameterValue());
        $this->assertEquals(MessageCodes::IS_INT, $errors[0]->getMessageCode());
        $this->assertEquals(MessageCodes::IS_STRING, $errors[1]->getMessageCode());
        $this->assertEquals(MessageCodes::IS_BOOL, $errors[2]->getMessageCode());
        $this->assertEquals(MessageCodes::IS_FLOAT, $errors[3]->getMessageCode());
        $this->assertEquals(MessageCodes::IS_DATE_TIME, $errors[4]->getMessageCode());
        $this->assertEquals(MessageCodes::MORE_THAN, $errors[5]->getMessageCode());

        $this->assertEquals($paramName, $identityCapture->getParameterName());

        $this->assertEquals([], $this->aggregator->getCaptures());

        $this->assertTrue($idRule->isStateless());
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
     * @param ValidatorInterface $validator
     * @param mixed              $input
     *
     * @return Error[]
     */
    private function readErrors(ValidatorInterface $validator, $input)
    {
        $errors = [];
        foreach ($validator->validate($input) as $error) {
            /** @var Error $error */
            $errors[] = $error;
        }

        return $errors;
    }
}
