<?php namespace Limoncello\Passport\Adaptors\MySql;

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
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use PDO;

/**
 * @package Limoncello\Passport
 */
class TokenRepository extends \Limoncello\Passport\Repositories\TokenRepository
{
    /**
     * @var string
     */
    private $modelClass;

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $databaseScheme
     * @param string                  $modelClass
     */
    public function __construct(
        Connection $connection,
        DatabaseSchemeInterface $databaseScheme,
        string $modelClass = Token::class
    ) {
        $this->setConnection($connection)->setDatabaseScheme($databaseScheme);
        $this->modelClass = $modelClass;
    }

    /**
     * @inheritdoc
     */
    public function readPassport(string $tokenValue, int $expirationInSeconds)
    {
        $scheme = $this->getDatabaseScheme();
        $query  = $this->getConnection()->createQueryBuilder();
        $query  = $this->addExpirationCondition(
            $query->select(['*'])
                ->from($scheme->getPassportView())
                ->where($scheme->getTokensValueColumn() . '=' . $this->createTypedParameter($query, $tokenValue))
                ->andWhere($query->expr()->eq($this->getDatabaseScheme()->getTokensIsEnabledColumn(), '1')),
            $expirationInSeconds,
            $scheme->getTokensValueCreatedAtColumn()
        );

        $statement = $query->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $data = $statement->fetch();

        $result = null;
        if ($data !== false) {
            $scopesColumn        = $scheme->getTokensViewScopesColumn();
            $scopeList           = $data[$scopesColumn];
            $data[$scopesColumn] = explode(' ', $scopeList);
            $result              = $data;
        }

        return $result;
    }

    /**
     * @param string      $token
     * @param int         $expirationInSeconds
     * @param string      $userClass
     * @param Type[]|null $attributeTypes
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function readUserByToken(
        string $token,
        int $expirationInSeconds,
        string $userClass,
        array $attributeTypes = null
    ): array {
        $query = $this->getConnection()->createQueryBuilder();

        $scheme            = $this->getDatabaseScheme();
        $createdAtColumn   = $scheme->getTokensValueCreatedAtColumn();
        $tokensValueColumn = $scheme->getTokensValueColumn();
        $scopesColumn      = $scheme->getClientsViewScopesColumn();
        $statement         = $this->addExpirationCondition(
            $query->select(['*'])
                ->from($scheme->getUsersView())
                ->where($tokensValueColumn . '=' . $this->createTypedParameter($query, $token)),
            $expirationInSeconds,
            $createdAtColumn
        )->execute();

        if ($attributeTypes === null) {
            $result = $this->fetchUnTyped($statement, $userClass, $scopesColumn, $tokensValueColumn, $createdAtColumn);
        } else {
            $result = $this->fetchTyped(
                $statement,
                $this->getConnection()->getDatabasePlatform(),
                $userClass,
                $scopesColumn,
                $tokensValueColumn,
                $createdAtColumn,
                $attributeTypes
            );
        }

        return $result;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param Statement        $statement
     * @param AbstractPlatform $platform
     * @param string           $modelClass
     * @param string           $scopesColumn
     * @param string           $tokenValueColumn
     * @param string           $createdAtColumn
     * @param Type[]           $attributeTypes
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function fetchTyped(
        Statement $statement,
        AbstractPlatform $platform,
        string $modelClass,
        string $scopesColumn,
        string $tokenValueColumn,
        string $createdAtColumn,
        array $attributeTypes
    ): array {
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $attributesOrFalse = $statement->fetch();
        if ($attributesOrFalse === false) {
            $model   = null;
            $scopes = null;
        } else {
            $scopes = $attributesOrFalse[$scopesColumn];
            unset($attributesOrFalse[$scopesColumn]);
            unset($attributesOrFalse[$tokenValueColumn]);
            unset($attributesOrFalse[$createdAtColumn]);
            $model = new $modelClass();
            foreach ($attributesOrFalse as $name => $value) {
                if (array_key_exists($name, $attributeTypes) === true) {
                    /** @var Type $type */
                    $type  = $attributeTypes[$name];
                    $value = $type->convertToPHPValue($value, $platform);
                }
                $model->{$name} = $value;
            }
        }

        return [$model, $scopes];
    }

    /**
     * @param Statement $statement
     * @param string    $modelClass
     * @param string    $scopesColumn
     * @param string    $tokenValueColumn
     * @param string    $createdAtColumn
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function fetchUnTyped(
        Statement $statement,
        string $modelClass,
        string $scopesColumn,
        string $tokenValueColumn,
        string $createdAtColumn
    ): array {
        $statement->setFetchMode(PDO::FETCH_CLASS, $modelClass);
        $modelOrFalse = $statement->fetch();
        if ($modelOrFalse === false) {
            $model   = null;
            $scopes = null;
        } else {
            $scopes = $modelOrFalse->{$scopesColumn};
            unset($modelOrFalse->{$scopesColumn});
            unset($modelOrFalse->{$tokenValueColumn});
            unset($modelOrFalse->{$createdAtColumn});
            $model = $modelOrFalse;
        }

        return [$model, $scopes];
    }

    /**
     * @inheritdoc
     */
    protected function getClassName(): string
    {
        return $this->modelClass;
    }

    /**
     * @inheritdoc
     */
    protected function getTableNameForReading(): string
    {
        return $this->getDatabaseScheme()->getTokensView();
    }
}
