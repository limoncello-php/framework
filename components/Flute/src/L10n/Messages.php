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
interface Messages extends \Limoncello\Validation\I18n\Messages
{
    /** @var string Namespace name for message keys. */
    const NAMESPACE_NAME = 'Limoncello.Flute';

    /** @var string Validation Message Template */
    const MSG_ERR_INVALID_ARGUMENT = 'Invalid argument.';

    /** @var string Validation Message Template */
    const MSG_ERR_INVALID_JSON_DATA_IN_REQUEST = 'Invalid JSON data in request.';

    /** @var string Validation Message Template */
    const MSG_ERR_CANNOT_CREATE_NON_UNIQUE_RESOURCE = 'Cannot create non unique resource.';

    /** @var string Validation Message Template */
    const MSG_ERR_CANNOT_UPDATE_WITH_UNIQUE_CONSTRAINT_VIOLATION =
        'Cannot update resource because unique constraint violated.';

    /** @var string Validation Message Template */
    const TYPE_MISSING = 'JSON API type should be specified.';

    /** @var string Validation Message Template */
    const INVALID_ATTRIBUTES = 'JSON API attributes are invalid.';

    /** @var string Validation Message Template */
    const UNKNOWN_ATTRIBUTE = 'Unknown JSON API attribute.';

    /** @var string Validation Message Template */
    const INVALID_RELATIONSHIP_TYPE = 'The value should be a valid JSON API relationship type.';

    /** @var string Validation Message Template */
    const INVALID_RELATIONSHIP = 'Invalid JSON API relationship.';

    /** @var string Validation Message Template */
    const UNKNOWN_RELATIONSHIP = 'Unknown JSON API relationship.';

    /** @var string Validation Message Template */
    const EXIST_IN_DATABASE_SINGLE = 'The value should be a valid identifier.';

    /** @var string Validation Message Template */
    const EXIST_IN_DATABASE_MULTIPLE = 'The value should be valid identifiers.';

    /** @var string Validation Message Template */
    const UNIQUE_IN_DATABASE_SINGLE = 'The value should be a unique identifier.';

    /** @var string Validation Message Template */
    const INVALID_OPERATION_ARGUMENTS = 'Invalid Operation Arguments.';

    /** @var string Validation Message Template */
    const INVALID_UUID = 'The value should be a valid UUID.';
}
