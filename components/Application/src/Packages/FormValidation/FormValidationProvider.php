<?php namespace Limoncello\Application\Packages\FormValidation;

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

use Limoncello\Application\FormValidation\Validator;
use Limoncello\Application\Resources\Messages\En\Validation;
use Limoncello\Contracts\Provider\ProvidesContainerConfiguratorsInterface;
use Limoncello\Contracts\Provider\ProvidesMessageResourcesInterface;

/**
 * @package Limoncello\Application
 */
class FormValidationProvider implements ProvidesContainerConfiguratorsInterface, ProvidesMessageResourcesInterface
{
    /**
     * @inheritdoc
     */
    public static function getContainerConfigurators(): array
    {
        return [
            FormValidationContainerConfigurator::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getMessageDescriptions(): array
    {
        return [
            ['en', Validator::RESOURCES_NAMESPACE, Validation::class],
        ];
    }
}
