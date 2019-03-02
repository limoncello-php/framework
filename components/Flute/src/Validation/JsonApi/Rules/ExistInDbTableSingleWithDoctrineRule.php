<?php declare (strict_types = 1);

namespace Limoncello\Flute\Validation\JsonApi\Rules;

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

use Doctrine\DBAL\Connection;
use Limoncello\Flute\Contracts\Validation\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Rules\ExecuteRule;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @package Limoncello\Flute
 */
final class ExistInDbTableSingleWithDoctrineRule extends ExecuteRule
{
    /**
     * Property key.
     */
    const PROPERTY_TABLE_NAME = self::PROPERTY_LAST + 1;

    /**
     * Property key.
     */
    const PROPERTY_PRIMARY_NAME = self::PROPERTY_TABLE_NAME + 1;

    /**
     * @param string $tableName
     * @param string $primaryName
     */
    public function __construct(string $tableName, string $primaryName)
    {
        parent::__construct([
            static::PROPERTY_TABLE_NAME   => $tableName,
            static::PROPERTY_PRIMARY_NAME => $primaryName,
        ]);
    }

    /**
     * @param mixed            $value
     * @param ContextInterface $context
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function execute($value, ContextInterface $context): array
    {
        $count = 0;

        if (is_scalar($value) === true) {
            $tableName   = $context->getProperties()->getProperty(static::PROPERTY_TABLE_NAME);
            $primaryName = $context->getProperties()->getProperty(static::PROPERTY_PRIMARY_NAME);

            /** @var Connection $connection */
            $connection = $context->getContainer()->get(Connection::class);
            $builder    = $connection->createQueryBuilder();
            $statement  = $builder
                ->select('count(*)')
                ->from($tableName)
                ->where($builder->expr()->eq($primaryName, $builder->createPositionalParameter($value)))
                ->execute();

            $count = $statement->fetchColumn();
        }

        $reply = $count > 0 ?
            static::createSuccessReply($value) :
            static::createErrorReply($context, $value, ErrorCodes::EXIST_IN_DATABASE_SINGLE);

        return $reply;
    }
}
