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
use Limoncello\Flute\Contracts\Validation\FormValidatorInterface;
use Limoncello\Flute\Package\FluteSettings;
use Limoncello\Flute\Validation\Form\Execution\FormRulesSerializer;
use Limoncello\Flute\Validation\Form\FormValidator;
use Limoncello\Tests\Flute\Data\L10n\FormatterFactory;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Validation\Forms\CreateCommentRules;
use Limoncello\Validation\Execution\BlockSerializer;
use Limoncello\Validation\Execution\ContextStorage;
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
        $this->assertNotNull($validator = $this->createValidator(CreateCommentRules::class));

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
        $this->assertNotNull($validator = $this->createValidator(CreateCommentRules::class));

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
     * @param string $rulesClass
     *
     * @return FormValidatorInterface
     */
    private function createValidator(string $rulesClass): FormValidatorInterface
    {
        $serializer = new FormRulesSerializer(new BlockSerializer());
        $serializer->addRulesFromClass($rulesClass);

        $container = null;
        $validator = new FormValidator(
            $rulesClass,
            FormRulesSerializer::class,
            $serializer->getData(),
            new ContextStorage($serializer->getBlocks(), $container),
            (new FormatterFactory())->createFormatter(FluteSettings::VALIDATION_NAMESPACE)
        );

        return $validator;
    }
}
