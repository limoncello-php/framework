<?php declare (strict_types = 1);

namespace Limoncello\Data\Migrations;

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
 * @package Limoncello\Data
 */
final class RelationshipRestrictions
{
    /** @var string Prevents deletion of a referenced row */
    public const RESTRICT = 'RESTRICT';

    /** @var string Automatically deletes when a referenced row is deleted. */
    public const CASCADE = 'CASCADE';

    /** @var string Automatically sets to NULL when a referenced row is deleted. */
    public const SET_NULL = 'SET NULL';

    /** @var string Automatically sets a default value when a referenced row is deleted. */
    public const SET_DEFAULT = 'SET DEFAULT';
}
