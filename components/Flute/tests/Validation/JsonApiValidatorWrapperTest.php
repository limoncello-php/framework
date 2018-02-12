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

use Exception;
use Limoncello\Container\Container;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiValidatorInterface;
use Limoncello\Flute\Factory;
use Limoncello\Flute\Validation\JsonApi\Execution\JsonApiRuleSerializer;
use Limoncello\Flute\Validation\JsonApi\Validator;
use Limoncello\Flute\Validation\JsonApi\ValidatorWrapper;
use Limoncello\Tests\Flute\Data\L10n\FormatterFactory;
use Limoncello\Tests\Flute\Data\Schemes\CommentSchema;
use Limoncello\Tests\Flute\Data\Validation\AppRules as v;
use Limoncello\Tests\Flute\TestCase;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class JsonApiValidatorWrapperTest extends TestCase
{
    /**
     * Validation test.
     *
     * @throws Exception
     */
    public function testCaptureValidData(): void
    {
        $text = 'Outside every fat man there was an even fatter man trying to close in';

        $wrapper = $this->createValidatorWrapper();

        $int = 100;
        $wrapper->assert($this->createInput($text, $int));
        $captures = $wrapper->getJsonApiCaptures();
        $this->assertEmpty($wrapper->getJsonApiErrors());
        $this->assertCount(4, $captures);
        $this->assertSame(CommentSchema::TYPE, $captures[CommentSchema::RESOURCE_TYPE]);
        $this->assertNull($captures[CommentSchema::RESOURCE_ID]);
        // the wrapper reverts the text value if validation is successful
        $this->assertSame(strrev($text), $captures[CommentSchema::ATTR_TEXT]);
        $this->assertSame($int, $captures[CommentSchema::ATTR_INT]);
    }

    /**
     * Validation test.
     *
     * @throws Exception
     */
    public function testInvalidData(): void
    {
        $text = 'Outside every fat man there was an even fatter man trying to close in';

        $wrapper = $this->createValidatorWrapper();

        $int = 5; // the text is longer than this

        $exception = null;
        try {
            $wrapper->assert($this->createInput($text, $int));
        } catch (JsonApiException $exception) {
        }
        $this->assertNotNull($exception);

        $this->assertEmpty($wrapper->getJsonApiCaptures());
        $this->assertCount(1, $errors = $wrapper->getJsonApiErrors());
        $error = reset($errors);
        $this->assertEquals('Dependency condition failed.', $error->getDetail());
    }

    /**
     * @param string $text
     * @param int    $int
     *
     * @return array
     */
    private function createInput(string $text, int $int): array
    {
        $jsonInput = <<<EOT
        {
            "data" : {
                "type"  : "comments",
                "id"    : null,
                "attributes" : {
                    "text-attribute" : "$text",
                    "int-attribute"  : "$int"
                }
            }
        }
EOT;

        return json_decode($jsonInput, true);
    }

    /**
     * @return ContainerInterface
     */
    private function createContainer(): ContainerInterface
    {
        $container = new Container();

        $container[ModelSchemeInfoInterface::class]  = $schemes = $this->getModelSchemes();
        $container[JsonSchemesInterface::class]      = $this->getJsonSchemes(new Factory($container), $schemes);
        $container[FormatterFactoryInterface::class] = new FormatterFactory();

        return $container;
    }

    /**
     * @return JsonApiValidatorInterface
     */
    private function createValidatorWrapper(): JsonApiValidatorInterface
    {
        $container = $this->createContainer();
        $validator = $this->createSimplifiedCommentValidator($container);
        $wrapper   = new class ($validator) extends ValidatorWrapper
        {
            /**
             * @inheritdoc
             */
            public function validate($jsonData): bool
            {
                $this->initWrapper();

                $result = $this->getWrappedValidator()->validate($jsonData);
                if ($result === true) {
                    // extra validation rules
                    $captures  = $this->getWrappedValidator()->getJsonApiCaptures();
                    $textValue = $captures['text-attribute'];
                    $intValue  = $captures['int-attribute'];

                    // as an example of validation with dependencies let's model it with the
                    // text value being no longer than int value.

                    $result = strlen($textValue) <= $intValue;
                    if ($result === false) {
                        // with the collection it's easier to add errors
                        $errors  = new ErrorCollection();
                        $title   = 'Invalid Value';
                        $details = 'Dependency condition failed.';
                        $errors->addDataError($title, $details);
                        $this->setWrapperErrors($errors->getArrayCopy());
                    } else {
                        // just for fun (and testing of changing captures) let's reverse the text value
                        $this->setWrapperCaptures(['text-attribute' => strrev($textValue)]);
                    }
                }

                return $result;
            }
        };

        return $wrapper;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return JsonApiValidatorInterface
     */
    private function createSimplifiedCommentValidator(ContainerInterface $container): JsonApiValidatorInterface
    {
        $name = 'some_rule_name';
        $data = (new JsonApiRuleSerializer())->addResourceRules(
            $name,
            v::equals(null),
            v::equals('comments'),
            [
                CommentSchema::ATTR_TEXT => v::isString(v::stringLengthMin(1)),
                CommentSchema::ATTR_INT  => v::isString(v::stringToInt()),
            ],
            [],
            []
        )->getData();

        $validator = new Validator($name, $data, $container);

        return $validator;
    }
}
