<?php namespace Limoncello\Tests\Flute\Data\Http;

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
 * @package Limoncello\Tests\Flute
 */
abstract class BaseController extends \Limoncello\Flute\Http\BaseController
{
    /** URI key used in routing table */
    const ROUTE_KEY_CHILD_INDEX = 'child_idx';
}
