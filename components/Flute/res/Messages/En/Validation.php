<?php namespace Limoncello\Flute\Resources\Messages\En;

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

use Limoncello\Contracts\L10n\MessageStorageInterface;
use Limoncello\Flute\Contracts\Validation\ErrorCodes;
use Limoncello\Validation\I18n\EnUsLocale;

/**
 * @package Limoncello\Flute
 */
class Validation implements MessageStorageInterface
{
    /**
     * @inheritdoc
     */
    public static function getMessages(): array
    {
        return
            EnUsLocale::MESSAGES +

            [
                ErrorCodes::TYPE_MISSING               => 'JSON API type should be specified.',
                ErrorCodes::INVALID_ATTRIBUTES         => 'JSON API attributes are invalid.',
                ErrorCodes::UNKNOWN_ATTRIBUTE          => 'Unknown JSON API attribute.',
                ErrorCodes::INVALID_RELATIONSHIP_TYPE  => 'The value should be a valid JSON API relationship type.',
                ErrorCodes::INVALID_RELATIONSHIP       => 'Invalid JSON API relationship.',
                ErrorCodes::UNKNOWN_RELATIONSHIP       => 'Unknown JSON API relationship.',
                ErrorCodes::EXIST_IN_DATABASE_SINGLE   => 'The value should be a valid identifier.',
                ErrorCodes::EXIST_IN_DATABASE_MULTIPLE => 'The value should be valid identifiers.',
                ErrorCodes::UNIQUE_IN_DATABASE_SINGLE  => 'The value should be a unique identifier.',
            ];
    }
}
