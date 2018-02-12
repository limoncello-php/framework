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
use Generator;
use Limoncello\Container\Container;
use Limoncello\Flute\Contracts\Validation\FormValidatorInterface;
use Limoncello\Flute\Package\FluteSettings as C;
use Limoncello\Flute\Validation\Form\Execution\AttributeRulesSerializer;
use Limoncello\Flute\Validation\Form\Validator;
use Limoncello\Tests\Flute\Data\L10n\FormatterFactory;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Validation\FormRuleSets\CreateCommentRuleSet;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Application
 */
class FormValidatorTest extends TestCase
{
    /**
     * @return void
     *
     * @throws Exception
     */
    public function testValidator(): void
    {
        $this->assertNotNull($validator = $this->createValidator(CreateCommentRuleSet::getAttributeRules()));

        $this->assertTrue($validator->validate([Comment::FIELD_TEXT => 'some text']));
        $this->assertFalse($validator->validate([Comment::FIELD_TEXT => false]));
        $this->assertEquals(
            [Comment::FIELD_TEXT => 'The value should be a string.'],
            $this->iterableToArray($validator->getMessages())
        );
    }

    /**
     * @return void
     *
     * @throws Exception
     *
     * @expectedException \Limoncello\Flute\Exceptions\InvalidArgumentException
     */
    public function testInvalidInput(): void
    {
        $this->assertNotNull($validator = $this->createValidator(CreateCommentRuleSet::getAttributeRules()));

        $validator->validate('not array');
    }

    /**
     * @param iterable $iterable
     *
     * @return array
     */
    private function iterableToArray(iterable $iterable): array
    {
        return $iterable instanceof Generator ? iterator_to_array($iterable) : $iterable;
    }

    /**
     * @param array $attributeRules
     *
     * @return FormValidatorInterface
     */
    private function createValidator(array $attributeRules): FormValidatorInterface
    {
        $name      = 'typically_a_class_name';
        $container = new Container();

        $data    = (new AttributeRulesSerializer())->addResourceRules($name, $attributeRules)->getData();
        $factory = new FormatterFactory();

        $validator = new Validator($name, $data, $container, $factory->createFormatter(C::VALIDATION_NAMESPACE));

        return $validator;
    }
}
