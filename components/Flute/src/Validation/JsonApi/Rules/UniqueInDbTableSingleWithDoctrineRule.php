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
use Limoncello\Flute\L10n\Messages;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Rules\ExecuteRule;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use function is_scalar;

/**
 * @package Limoncello\Flute
 */
final class UniqueInDbTableSingleWithDoctrineRule extends ExecuteRule
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
     * Property key.
     */
    const PROPERTY_PRIMARY_KEY_NAME = self::PROPERTY_PRIMARY_NAME + 1;

    /**
     * @param string      $tableName
     * @param string      $primaryName
     * @param string|null $primaryKeyName
     */
    public function __construct(string $tableName, string $primaryName, ?string $primaryKeyName = null)
    {
        parent::__construct([
            static::PROPERTY_TABLE_NAME       => $tableName,
            static::PROPERTY_PRIMARY_NAME     => $primaryName,
            static::PROPERTY_PRIMARY_KEY_NAME => $primaryKeyName,
        ]);
    }

    /**
     * @param mixed            $value
     * @param ContextInterface $context
     * @param null             $primaryKeyValue
     *
     * @return array
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     */
    public static function execute($value, ContextInterface $context, $primaryKeyValue = null): array
    {
        $found = false;

        if (is_scalar($value) === true) {
            /** @var Connection $connection */
            $connection  = $context->getContainer()->get(Connection::class);
            $builder     = $connection->createQueryBuilder();
            $tableName   = $context->getProperties()->getProperty(static::PROPERTY_TABLE_NAME);
            $primaryName = $context->getProperties()->getProperty(static::PROPERTY_PRIMARY_NAME);
            $primaryKeyName = $context->getProperties()->getProperty(static::PROPERTY_PRIMARY_KEY_NAME);
            $columnsName = $primaryKeyName !== null ? "`{$primaryKeyName}`, `{$primaryName}`" : "`{$primaryName}`";
            $statement   = $builder
                ->select($columnsName)
                ->from($tableName)
                ->where($builder->expr()->eq($primaryName, $builder->createPositionalParameter($value)))
                ->setMaxResults(1);

            $fetched = $statement->execute()->fetch();

            $found = isset($primaryKeyName) ?
                $fetched !== false && (int)$fetched[$primaryKeyName] !== $primaryKeyValue :
                $fetched !== false;
        }

        $reply = $found === false ?
            static::createSuccessReply($value) :
            static::createErrorReply(
                $context,
                $value,
                ErrorCodes::UNIQUE_IN_DATABASE_SINGLE,
                Messages::UNIQUE_IN_DATABASE_SINGLE,
                []
            );

        return $reply;
    }
}
