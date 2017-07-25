<?php namespace Limoncello\Flute\Validation\Rules;

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
use Limoncello\Flute\Contracts\Validation\ContextInterface;
use Limoncello\Flute\Contracts\Validation\ErrorCodes;
use Limoncello\Validation\Blocks\ProcedureBlock;
use Limoncello\Validation\Contracts\Blocks\ExecutionBlockInterface;
use Limoncello\Validation\Execution\BlockReplies;
use Limoncello\Validation\Rules\BaseRule;

/**
 * @package Limoncello\Flute
 */
final class UniqueInDbTableSingleWithDoctrine extends BaseRule
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
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private $primaryName;

    /**
     * @param string $tableName
     * @param string $primaryName
     */
    public function __construct(string $tableName, string $primaryName)
    {
        $this->tableName   = $tableName;
        $this->primaryName = $primaryName;
    }

    /**
     * @inheritdoc
     */
    public function toBlock(): ExecutionBlockInterface
    {
        $customProperties = [
            self::PROPERTY_TABLE_NAME   => $this->getTableName(),
            self::PROPERTY_PRIMARY_NAME => $this->getPrimaryName(),
        ];

        return (new ProcedureBlock([self::class, 'execute']))
            ->setProperties($this->getStandardProperties() + $customProperties);
    }

    /**
     * @param mixed            $value
     * @param ContextInterface $context
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function execute($value, ContextInterface $context): array
    {
        $count = 0;

        if (is_scalar($value) === true) {
            /** @var Connection $connection */
            $connection  = $context->getContainer()->get(Connection::class);
            $builder     = $connection->createQueryBuilder();
            $tableName   = $context->getProperties()->getProperty(self::PROPERTY_TABLE_NAME);
            $primaryName = $context->getProperties()->getProperty(self::PROPERTY_PRIMARY_NAME);
            $statement   = $builder
                ->select('count(*)')
                ->from($tableName)
                ->where($builder->expr()->eq($primaryName, $builder->createPositionalParameter($value)))
                ->execute();

            $count = $statement->fetchColumn();
        }

        $reply = $count <= 0 ?
            BlockReplies::createSuccessReply($value) :
            BlockReplies::createErrorReply($context, $value, ErrorCodes::UNIQUE_IN_DATABASE_SINGLE);

        return $reply;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @return string
     */
    public function getPrimaryName(): string
    {
        return $this->primaryName;
    }
}
