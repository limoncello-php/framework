<?php namespace Limoncello\Tests\Flute\Data\Api;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use Doctrine\DBAL\DBALException;
use Limoncello\Flute\Adapters\ModelQueryBuilder;
use Limoncello\Flute\Contracts\Http\Query\FilterParameterInterface;
use Limoncello\Tests\Flute\Data\Models\Comment;

/**
 * @package Limoncello\Tests\Flute
 */
class CommentsApi extends AppCrud
{
    const MODEL_CLASS = Comment::class;

    const DEBUG_KEY_DEFAULT_FILTER_INDEX = true;

    /** @var bool Key for tests */
    public static $isFilterIndexForCurrentUser = self::DEBUG_KEY_DEFAULT_FILTER_INDEX;

    /**
     * @inheritdoc
     *
     * @throws DBALException
     */
    protected function builderOnIndex(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        $builder = parent::builderOnIndex($builder);

        if (static::$isFilterIndexForCurrentUser) {
            // suppose we want to limit API `index` method to only comments of current user
            // we can extend builder here

            $curUserId = 1;
            $builder->addFiltersWithAndToAlias([
                Comment::FIELD_ID_USER => [
                    FilterParameterInterface::OPERATION_EQUALS => [$curUserId],
                ],
            ]);
        }

        return $builder;
    }

    /**
     * @inheritdoc
     */
    public function create($index, array $attributes, array $toMany): string
    {
        // suppose we want to create comments using current user as an author.
        $curUserId                          = 1;
        $attributes[Comment::FIELD_ID_USER] = $curUserId;

        return parent::create($index, $attributes, $toMany);
    }
}
