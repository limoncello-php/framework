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
        /** @var Token|null $token */
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
        /** @var Token $token */
        $token = parent::readByCode($code, $expirationInSeconds);
        if ($token !== null) {
            $this->addScope($token);
        }

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function readByValue(string $code, int $expirationInSeconds)
    {
        /** @var Token $token */
        $token = parent::readByValue($code, $expirationInSeconds);
        if ($token !== null) {
            $this->addScope($token);
        }

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function readByRefresh(string $code, int $expirationInSeconds)
    {
        /** @var Token $token */
        $token = parent::readByRefresh($code, $expirationInSeconds);
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
     * @param Token $token
     *
     * @return void
     */
    private function addScope(Token $token)
    {
        $token->setTokenScopeStrings($this->readScopeIdentifiers($token->getIdentifier()));
    }
}
