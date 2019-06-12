<?php namespace Limoncello\Contracts\Passport;

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

use Limoncello\Contracts\Authentication\AccountManagerInterface;

/**
 * @package Limoncello\Passport
 */
interface PassportAccountManagerInterface extends AccountManagerInterface
{
    /**
     * @param string $value
     *
     * @return PassportAccountInterface
     */
    public function setAccountWithTokenValue(string $value): PassportAccountInterface;

    /**
     * @return PassportAccountInterface|null
     */
    public function getPassport(): ?PassportAccountInterface;
}
