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

use Doctrine\DBAL\Connection;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use Limoncello\Passport\Contracts\Entities\TokenInterface;

/**
 * @package Limoncello\Passport
 */
class TokenRepository extends \Limoncello\Passport\Repositories\TokenRepository
{
    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $databaseScheme
     */
    public function __construct(Connection $connection, DatabaseSchemeInterface $databaseScheme)
    {
        $this->setConnection($connection)->setDatabaseScheme($databaseScheme);
    }

    /**
     * @inheritdoc
     */
    public function read(int $identifier)
    {
        $token = parent::read($identifier);

        if ($token !== null) {
            $this->addScope($token);
        }

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function readByCode(string $code, int $expirationInSeconds)
    {
        $token = parent::readByCode($code, $expirationInSeconds);
        if ($token !== null) {
            $this->addScope($token);
        }

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function readByValue(string $tokenValue, int $expirationInSeconds)
    {
        $token = parent::readByValue($tokenValue, $expirationInSeconds);
        if ($token !== null) {
            $this->addScope($token);
        }

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function readByRefresh(string $refreshValue, int $expirationInSeconds)
    {
        $token = parent::readByRefresh($refreshValue, $expirationInSeconds);
        if ($token !== null) {
            $this->addScope($token);
        }

        return $token;
    }

    /**
     * @inheritdoc
     */
    protected function getClassName(): string
    {
        return Token::class;
    }

    /**
     * @param TokenInterface $token
     *
     * @return void
     */
    private function addScope(TokenInterface $token)
    {
        $token->setScopeIdentifiers($this->readScopeIdentifiers($token->getIdentifier()));
    }
}
