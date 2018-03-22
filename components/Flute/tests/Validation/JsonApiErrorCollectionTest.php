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
use Limoncello\Flute\Contracts\Validation\ErrorCodes;
use Limoncello\Flute\Package\FluteSettings;
use Limoncello\Flute\Validation\JsonApi\Execution\JsonApiErrorCollection;
use Limoncello\Tests\Flute\Data\L10n\FormatterFactory;
use Limoncello\Tests\Flute\TestCase;
use Limoncello\Validation\Errors\Error;

/**
 * @package Limoncello\Tests\Flute
 */
class JsonApiErrorCollectionTest extends TestCase
{
    /**
     * Test adding errors.
     *
     * @throws Exception
     */
    public function testAddIdAndTypeErrors(): void
    {
        $formatter  = (new FormatterFactory())->createFormatter(FluteSettings::VALIDATION_NAMESPACE);
        $collection = new JsonApiErrorCollection($formatter);

        $this->assertCount(0, $collection);

        $collection->addValidationIdError(new Error('id', 'whatever', ErrorCodes::INVALID_VALUE, null));
        $collection->addValidationTypeError(new Error('id', 'whatever', ErrorCodes::TYPE_MISSING, null));

        $this->assertCount(2, $collection);
        $errors = $collection->getArrayCopy();

        $this->assertEquals(['pointer' => '/data/id'], $errors[0]->getSource());
        $this->assertEquals('The value is invalid.', $errors[0]->getDetail());

        $this->assertEquals(['pointer' => '/data/type'], $errors[1]->getSource());
        $this->assertEquals('JSON API type should be specified.', $errors[1]->getDetail());
    }
}
