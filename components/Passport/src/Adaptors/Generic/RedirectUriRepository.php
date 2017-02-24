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
class RedirectUriRepository extends \Limoncello\Passport\Repositories\RedirectUriRepository
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
    protected function getClassName(): string
    {
        return RedirectUri::class;
    }
}
