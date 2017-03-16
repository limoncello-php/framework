<?php namespace Limoncello\Flute\Contracts\I18n;

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

/**
 * @package Limoncello\Flute
 */
interface TranslatorInterface
{
    /** Message id */
    const MSG_ERR_INVALID_ELEMENT = 'Invalid element.';

    /** Message id */
    const MSG_ERR_CANNOT_BE_EMPTY = 'Value can not be empty.';

    /** Message id */
    const MSG_ERR_UNKNOWN_MODEL_CLASS = 'Unknown model class.';

    /** Message id */
    const MSG_ERR_QUERY_IS_NOT_CONFIGURED = 'Builder query is not configured.';

    /** Message id */
    const MSG_ERR_INVALID_PARAMETER = 'Invalid parameter.';

    /** Message id */
    const MSG_ERR_INVALID_OPERATION = 'Invalid operation.';

    /**
     * @param string $messageId
     *
     * @return string
     */
    public function get($messageId);
}
