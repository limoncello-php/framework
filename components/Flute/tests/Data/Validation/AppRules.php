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

use Limoncello\Flute\Validation\Rules\ApiRulesTrait;
use Limoncello\Flute\Validation\Rules\DatabaseRulesTrait;
use Limoncello\Flute\Validation\Rules\RelationshipRulesTrait;
use Limoncello\Tests\Flute\Data\Api\PostsApi;
use Limoncello\Tests\Flute\Data\Models\Category;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Models\Emotion;
use Limoncello\Tests\Flute\Data\Models\Post;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Rules;

/**
 * @package Limoncello\Tests\Flute
 */
class AppRules extends Rules
{
    use RelationshipRulesTrait, DatabaseRulesTrait, ApiRulesTrait;

    /**
     * @return RuleInterface
     */
    public static function postId(): RuleInterface
    {
        return static::stringToInt(static::exists(Post::TABLE_NAME, Post::FIELD_ID));
    }

    /**
     * @return RuleInterface
     */
    public static function readablePost(): RuleInterface
    {
        return static::stringToInt(static::readable(PostsApi::class));
    }

    /**
     * @return RuleInterface
     */
    public static function commentId(): RuleInterface
    {
        return static::stringToInt(static::exists(Comment::TABLE_NAME, Comment::FIELD_ID));
    }

    /**
     * @return RuleInterface
     */
    public static function categoryId(): RuleInterface
    {
        return static::stringToInt(static::exists(Category::TABLE_NAME, Category::FIELD_ID));
    }

    /**
     * @return RuleInterface
     */
    public static function emotionId(): RuleInterface
    {
        return static::stringToInt(static::exists(Emotion::TABLE_NAME, Emotion::FIELD_ID));
    }

    /**
     * @return RuleInterface
     */
    public static function emotionIds(): RuleInterface
    {
        return static::stringArrayToIntArray(
            static::existAll(Emotion::TABLE_NAME, Emotion::FIELD_ID)
        );
    }

    /**
     * @return RuleInterface
     */
    public static function readableEmotions(): RuleInterface
    {
        return static::stringArrayToIntArray(static::readableAll(PostsApi::class));
    }

    /**
     * @return RuleInterface
     */
    public static function userIdWithoutCheckInDatabase(): RuleInterface
    {
        return static::stringToInt();
    }
}
