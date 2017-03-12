<?php namespace Limoncello\Passport\Repositories;

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

use DateTimeImmutable;
use Limoncello\Passport\Contracts\Entities\RedirectUriInterface;
use Limoncello\Passport\Contracts\Repositories\RedirectUriRepositoryInterface;
use PDO;

/**
 * @package Limoncello\Passport
 */
abstract class RedirectUriRepository extends BaseRepository implements RedirectUriRepositoryInterface
{
    /**
     * @inheritdoc
     */
    public function indexClientUris(string $clientIdentifier): array
    {
        $query = $this->getConnection()->createQueryBuilder();

        $clientIdColumn = $this->getDatabaseScheme()->getRedirectUrisClientIdentityColumn();
        $statement      = $query
            ->select(['*'])
            ->from($this->getTableNameForWriting())
            ->where($clientIdColumn . '=' . $this->createTypedParameter($query, $clientIdentifier))
            ->execute();

        $statement->setFetchMode(PDO::FETCH_CLASS, $this->getClassName());
        $result = $statement->fetchAll();

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function create(RedirectUriInterface $redirectUri): RedirectUriInterface
    {
        $now        = new DateTimeImmutable();
        $scheme     = $this->getDatabaseScheme();
        $identifier = $this->createResource([
            $scheme->getRedirectUrisClientIdentityColumn() => $redirectUri->getClientIdentifier(),
            $scheme->getRedirectUrisValueColumn()          => $redirectUri->getValue(),
            $scheme->getRedirectUrisCreatedAtColumn()      => $now,
        ]);

        $redirectUri->setIdentifier($identifier)->setCreatedAt($now);

        return $redirectUri;
    }

    /**
     * @inheritdoc
     */
    public function read(int $identifier): RedirectUriInterface
    {
        return $this->readResource($identifier);
    }

    /**
     * @inheritdoc
     */
    public function update(RedirectUriInterface $redirectUri)
    {
        $now    = new DateTimeImmutable();
        $scheme = $this->getDatabaseScheme();
        $this->updateResource($redirectUri->getIdentifier(), [
            $scheme->getRedirectUrisClientIdentityColumn() => $redirectUri->getClientIdentifier(),
            $scheme->getRedirectUrisValueColumn()          => $redirectUri->getValue(),
            $scheme->getRedirectUrisUpdatedAtColumn()      => $now,
        ]);
        $redirectUri->setUpdatedAt($now);
    }

    /**
     * @inheritdoc
     */
    public function delete(int $identifier)
    {
        $this->deleteResource($identifier);
    }

    /**
     * @inheritdoc
     */
    protected function getTableNameForWriting(): string
    {
        return $this->getDatabaseScheme()->getRedirectUrisTable();
    }

    /**
     * @inheritdoc
     */
    protected function getPrimaryKeyName(): string
    {
        return $this->getDatabaseScheme()->getRedirectUrisIdentityColumn();
    }
}
