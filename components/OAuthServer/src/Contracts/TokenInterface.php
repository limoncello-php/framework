<?php namespace Limoncello\OAuthServer\Contracts;

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
 * @package Limoncello\OAuthServer
 */
interface TokenInterface
{
    /**
     * @return string
     */
    public function getClientIdentifier(): string;

    /**
     * @return string|int|null
     */
    public function getUserIdentifier();

    /**
     * @return string[]
     */
    public function getScopeIdentifiers(): array;

    /**
     * @return string|null
     */
    public function getValue();

    /**
     * @return string|null
     */
    public function getRefreshValue();
}
