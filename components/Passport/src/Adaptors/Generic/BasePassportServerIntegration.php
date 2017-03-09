<?php namespace Limoncello\Passport\Adaptors\Generic;

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

use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Limoncello\Passport\Contracts\Repositories\ClientRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\TokenRepositoryInterface;

/**
 * @package Limoncello\Passport
 */
abstract class BasePassportServerIntegration extends \Limoncello\Passport\Integration\BasePassportServerIntegration
{
    /**
     * @var ClientRepositoryInterface|null
     */
    private $clientRepo;

    /**
     * @var TokenRepositoryInterface|null
     */
    private $tokenRepo;

    /**
     * @inheritdoc
     */
    public function getClientRepository(): ClientRepositoryInterface
    {
        if ($this->clientRepo === null) {
            $this->clientRepo = new ClientRepository($this->getConnection(), $this->getDatabaseScheme());
        }

        return $this->clientRepo;
    }

    /**
     * @inheritdoc
     */
    public function getTokenRepository(): TokenRepositoryInterface
    {
        if ($this->tokenRepo === null) {
            $this->tokenRepo = new TokenRepository($this->getConnection(), $this->getDatabaseScheme());
        }

        return $this->tokenRepo;
    }

    /**
     * @inheritdoc
     */
    public function createTokenInstance(): TokenInterface
    {
        $token = new Token();

        return $token;
    }
}
