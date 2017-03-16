<?php namespace Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data;

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
 * @package Limoncello\Tests\Auth
 */
interface ContextProperties extends RequestProperties
{
    /** Context key */
    const PARAM_CURRENT_USER_ID = 'current_user_id';

    /** Context key */
    const PARAM_CURRENT_USER_ROLE = 'current_user_role';

    /** Context key */
    const PARAM_IS_WORK_TIME = 'is_work_time';

    /** Context key */
    const PARAM_USER_IS_SIGNED_IN = 'user_is_signed_in';
}
