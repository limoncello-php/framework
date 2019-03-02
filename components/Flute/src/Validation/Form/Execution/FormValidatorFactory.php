<?php declare (strict_types = 1);

namespace Limoncello\Flute\Validation\Form\Execution;

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

use Limoncello\Container\Traits\HasContainerTrait;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Flute\Contracts\Validation\FormValidatorFactoryInterface;
use Limoncello\Flute\Contracts\Validation\FormValidatorInterface;
use Limoncello\Flute\L10n\Validation;
use Limoncello\Flute\Package\FluteSettings as S;
use Limoncello\Flute\Validation\Form\FormValidator;
use Limoncello\Validation\Execution\ContextStorage;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Flute
 */
class FormValidatorFactory implements FormValidatorFactoryInterface
{
    use HasContainerTrait;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function createValidator(string $rulesClass): FormValidatorInterface
    {
        /** @var SettingsProviderInterface $settingsProvider */
        $settingsProvider = $this->getContainer()->get(SettingsProviderInterface::class);
        $serializedData   = S::getFormSerializedRules($settingsProvider->get(S::class));

        /** @var FormatterFactoryInterface $factory */
        $factory   = $this->getContainer()->get(FormatterFactoryInterface::class);
        $formatter = $factory->createFormatter(Validation::NAMESPACE_NAME);

        $validator = new FormValidator(
            $rulesClass,
            FormRulesSerializer::class,
            $serializedData,
            new ContextStorage(FormRulesSerializer::readBlocks($serializedData), $this->getContainer()),
            $formatter
        );

        return $validator;
    }
}
