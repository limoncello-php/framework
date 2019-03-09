<?php namespace Limoncello\Contracts\Authorization;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use Limoncello\Contracts\Exceptions\AuthorizationExceptionInterface;

/**
 * @package Limoncello\Contracts
 */
interface AuthorizationManagerInterface
{
    /**
     * @param string      $action
     * @param string|null $resourceType
     * @param string|null $resourceIdentity
     * @param array       $extraParams
     *
     * @return bool
     */
    public function isAllowed(
        string $action,
        string $resourceType = null,
        string $resourceIdentity = null,
        array $extraParams = []
    ): bool;

    /**
     * @param string      $action
     * @param string|null $resourceType
     * @param string|null $resourceIdentity
     * @param array       $extraParams
     *
     * @return void
     *
     * @throws AuthorizationExceptionInterface
     */
    public function authorize(
        string $action,
        string $resourceType = null,
        string $resourceIdentity = null,
        array $extraParams = []
    ): void;
}
