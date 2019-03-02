<?php declare (strict_types = 1);

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

namespace Limoncello\Tests\l10n\Messages\Resources2;

use Limoncello\Contracts\L10n\MessageStorageInterface;

/**
 * @package Limoncello\Tests\l10n
 */
class DeMessages implements MessageStorageInterface
{
    /**
     * @inheritdoc
     */
    public static function getMessages(): array
    {
        return [
            OriginalMessages::MSG_1 => 'Hallo Welt',
        ];
    }
}
