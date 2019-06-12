<?php declare(strict_types=1);

namespace Limoncello\Core\Routing\Traits;

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

/**
 * @package Limoncello\Core
 */
trait CallableTrait
{
    /**
     * @return string
     */
    protected function getCallableToCacheMessage(): string
    {
        return 'Value either not callable or cannot be cached or do not meet method signature requirements. ' .
            'Use callable in form of \'ClassName::methodName\' or [ClassName::class, \'methodName\'].';
    }
}
