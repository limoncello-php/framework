<?php namespace Limoncello\Tests\Flute\Data\Validation;

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
use Limoncello\Flute\Validation\Validator;
use Limoncello\Tests\Flute\Data\Models\Category;
use Limoncello\Tests\Flute\Data\Models\Emotion;
use Limoncello\Tests\Flute\Data\Models\Post;
use Limoncello\Tests\Flute\Data\Models\Role;
use Limoncello\Validation\Contracts\MessageCodes;
use Limoncello\Validation\Contracts\RuleInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class AppValidator extends Validator
{
    /**
     * @param ContainerInterface $container
     * @param string             $jsonType
     * @param array              $rules
     */
    public function __construct(ContainerInterface $container, string $jsonType, array $rules)
    {
        if (array_key_exists(static::RULE_UNLISTED_ATTRIBUTE, $rules) === false) {
            $rules[static::RULE_UNLISTED_ATTRIBUTE] = static::fail();
        }
        if (array_key_exists(static::RULE_UNLISTED_RELATIONSHIP, $rules) === false) {
            $rules[static::RULE_UNLISTED_RELATIONSHIP] = static::fail();
        }

        parent::__construct($container, $jsonType, $rules);
    }

    /**
     * @param int|string $index
     *
     * @return RuleInterface
     */
    protected function idEquals($index)
    {
        return static::equals($index);
    }

    /**
     * @return RuleInterface
     */
    protected function absentOrNull()
    {
        return static::isNull();
    }

    /**
     * @param int|null $maxLength
     *
     * @return RuleInterface
     */
    protected function requiredText($maxLength = null)
    {
        return static::required($this->optionalText($maxLength));
    }

    /**
     * @param int|null $maxLength
     *
     * @return RuleInterface
     */
    protected function optionalText($maxLength = null)
    {
        return static::andX(static::isString(), static::stringLength(1, $maxLength));
    }

    /**
     * @param int $messageCode
     *
     * @return RuleInterface
     */
    protected function requiredPostId($messageCode = MessageCodes::INVALID_VALUE)
    {
        return static::required($this->optionalPostId($messageCode));
    }

    /**
     * @param int $messageCode
     *
     * @return RuleInterface
     */
    protected function optionalPostId($messageCode = MessageCodes::INVALID_VALUE)
    {
        $exists = function ($index) {
            return static::exists($this->getConnection(), Post::TABLE_NAME, Post::FIELD_ID, $index);
        };

        return static::andX(static::isNumeric(), static::callableX($exists, $messageCode));
    }

    /**
     * @param Connection $connection
     * @param string     $tableName
     * @param string     $columnName
     * @param string     $value
     *
     * @return bool
     */
    protected static function exists(Connection $connection, string $tableName, string $columnName, string $value)
    {
        $query = $connection->createQueryBuilder();
        $query
            ->select($columnName)
            ->from($tableName)
            ->where($columnName . '=' . $query->createPositionalParameter($value))
            ->setMaxResults(1);


        $fetched = $query->execute()->fetch();
        $result  = $fetched !== false;

        return $result;
    }

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return $this->getContainer()->get(Connection::class);
    }

    /**
     * @param int $messageCode
     *
     * @return RuleInterface
     */
    protected function requiredRoleId($messageCode = MessageCodes::INVALID_VALUE)
    {
        return static::required($this->optionalRoleId($messageCode));
    }

    /**
     * @param int $messageCode
     *
     * @return RuleInterface
     */
    protected function optionalRoleId($messageCode = MessageCodes::INVALID_VALUE)
    {
        $exists = function ($index) {
            return static::exists($this->getConnection(), Role::TABLE_NAME, Role::FIELD_ID, $index);
        };

        return static::callableX($exists, $messageCode);
    }

    /**
     * @param int $messageCode
     *
     * @return RuleInterface
     */
    protected function optionalCategoryId($messageCode = MessageCodes::INVALID_VALUE)
    {
        $exists = function ($index) {
            return static::exists($this->getConnection(), Category::TABLE_NAME, Category::FIELD_ID, $index);
        };

        return static::andX(static::isNumeric(), static::callableX($exists, $messageCode));
    }

    /**
     * @param int $messageCode
     *
     * @return RuleInterface
     */
    protected function optionalEmotionId($messageCode = MessageCodes::INVALID_VALUE)
    {
        $exists = function ($index) {
            return static::exists($this->getConnection(), Emotion::TABLE_NAME, Emotion::FIELD_ID, $index);
        };

        return static::callableX($exists, $messageCode);
    }
}
