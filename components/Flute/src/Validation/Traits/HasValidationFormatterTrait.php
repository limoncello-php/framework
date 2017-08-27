<?php namespace Limoncello\Flute\Validation\Traits;

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

use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\Flute\Validation\Validator;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Flute
 *
 * @method ContainerInterface getContainer()
 */
trait HasValidationFormatterTrait
{
    /**
     * @return FormatterInterface
     */
    protected function createValidationFormatter(): FormatterInterface
    {
        /** @var FormatterFactoryInterface $factory */
        $factory   = $this->getContainer()->get(FormatterFactoryInterface::class);
        $formatter = $factory->createFormatter(Validator::RESOURCES_NAMESPACE);

        return $formatter;
    }
}
