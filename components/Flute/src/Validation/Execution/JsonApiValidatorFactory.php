<?php namespace Limoncello\Flute\Validation\Execution;

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

use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiValidatorFactoryInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiValidatorInterface;
use Limoncello\Flute\Package\FluteSettings;
use Limoncello\Flute\Validation\Validator;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Flute
 */
class JsonApiValidatorFactory implements JsonApiValidatorFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function createValidator(string $class): JsonApiValidatorInterface
    {
        /** @var SettingsProviderInterface $settingsProvider */
        $settingsProvider = $this->getContainer()->get(SettingsProviderInterface::class);
        $settings         = $settingsProvider->get(FluteSettings::class);
        $ruleSetsData     = $settings[FluteSettings::KEY_VALIDATION_RULE_SETS_DATA];

        $validator = new Validator($class, $ruleSetsData, $this->getContainer());

        return $validator;
    }

    /**
     * @return ContainerInterface
     */
    private function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
