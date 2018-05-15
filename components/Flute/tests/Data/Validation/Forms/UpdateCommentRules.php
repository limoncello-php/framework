<?php namespace Limoncello\Tests\Flute\Data\Validation\Forms;

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

use Limoncello\Flute\Contracts\Validation\FormRulesInterface;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Validation\AppRules as r;

/**
 * @package Limoncello\Tests
 */
class UpdateCommentRules implements FormRulesInterface
{
    /**
     * Validation rules are aimed to check readableXXX rules.
     *
     * @inheritdoc
     */
    public static function getAttributeRules(): array
    {
        return [
            Comment::REL_POST     => r::readablePost(),
            Comment::REL_EMOTIONS => r::readableEmotions(),
        ];
    }
}
