<?php namespace Limoncello\Validation\Rules;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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
 * @package Limoncello\Validation
 */
class Success extends BaseRule
{
    /**
     * @inheritdoc
     */
    public function validate($input)
    {
        // yield empty Generator to comply with interface
        foreach ([] as $item) {
            yield $item; // @codeCoverageIgnore
        }
    }
}
