<?php namespace Limoncello\Auth\Authentication;

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

use Limoncello\Auth\Contracts\Authentication\AccountInterface;
use Limoncello\Auth\Contracts\Authentication\AccountManagerInterface;

/**
 * @package Limoncello\Auth
 */
class AccountManager implements AccountManagerInterface
{
    /**
     * @var null|AccountInterface
     */
    private $account = null;

    /**
     * @inheritdoc
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @inheritdoc
     */
    public function setAccount(AccountInterface $account): AccountManagerInterface
    {
        $this->account = $account;

        return $this;
    }
}
