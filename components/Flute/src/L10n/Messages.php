<?php declare (strict_types = 1);

namespace Limoncello\Flute\L10n;

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
 * @package Limoncello\Flute
 */
interface Messages
{
    /** @var string Namespace name for message keys. */
    const NAMESPACE_NAME = 'Limoncello.Flute.Messages';

    /** Message id */
    const MSG_ERR_INVALID_ARGUMENT = 'Invalid argument.';

    /** Message id */
    const MSG_ERR_INVALID_JSON_DATA_IN_REQUEST = 'Invalid JSON data in request.';

    /** Message id */
    const MSG_ERR_CANNOT_CREATE_NON_UNIQUE_RESOURCE = 'Cannot create non unique resource.';

    /** Message id */
    const MSG_ERR_CANNOT_UPDATE_WITH_UNIQUE_CONSTRAINT_VIOLATION =
        'Cannot update resource because unique constraint violated.';
}
