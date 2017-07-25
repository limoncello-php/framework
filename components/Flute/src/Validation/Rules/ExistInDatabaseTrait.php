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

use Limoncello\Validation\Contracts\Rules\RuleInterface;

/**
 * @package Limoncello\Flute
 */
trait ExistInDatabaseTrait
{
    /**
     * @param string $tableName
     * @param string $primaryName
     *
     * @return RuleInterface
     */
    public static function exists(string $tableName, string $primaryName): RuleInterface
    {
        return new ExistInDbTableSingleWithDoctrine($tableName, $primaryName);
    }

    /**
     * @param string $tableName
     * @param string $primaryName
     *
     * @return RuleInterface
     */
    public static function existAll(string $tableName, string $primaryName): RuleInterface
    {
        return new ExistInDbTableMultipleWithDoctrine($tableName, $primaryName);
    }

    /**
     * @param string $tableName
     * @param string $primaryName
     *
     * @return RuleInterface
     */
    public static function unique(string $tableName, string $primaryName): RuleInterface
    {
        return new UniqueInDbTableSingleWithDoctrine($tableName, $primaryName);
    }
}
