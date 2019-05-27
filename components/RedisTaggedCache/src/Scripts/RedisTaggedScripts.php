<?php declare(strict_types=1);

namespace Limoncello\RedisTaggedCache\Scripts;

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

use function assert;

/**
 * @package Limoncello\RedisTaggedCache
 */
class RedisTaggedScripts
{
    /** @var int Script index */
    const ADD_VALUE_SCRIPT_INDEX = 0;

    /** @var int Script index */
    const REMOVE_VALUE_SCRIPT_INDEX = self::ADD_VALUE_SCRIPT_INDEX + 1;

    /** @var int Script index */
    const INVALIDATE_TAG_SCRIPT_INDEX = self::REMOVE_VALUE_SCRIPT_INDEX + 1;

    /**
     * @param int $scriptIndex
     *
     * @return string
     */
    public static function getScriptBody(int $scriptIndex): string
    {
        $scriptFolder = __DIR__ . DIRECTORY_SEPARATOR;

        switch ($scriptIndex) {
            case static::ADD_VALUE_SCRIPT_INDEX:
                return file_get_contents($scriptFolder . 'Add.lua');
            case static::REMOVE_VALUE_SCRIPT_INDEX:
                return file_get_contents($scriptFolder . 'RemoveImpl.lua') .
                    file_get_contents($scriptFolder . 'Remove.lua');
            default:
                assert($scriptIndex === static::INVALIDATE_TAG_SCRIPT_INDEX);
                return file_get_contents($scriptFolder . 'RemoveImpl.lua') .
                    file_get_contents($scriptFolder . 'InvalidateTag.lua');
        }
    }

    /**
     * @param int $scriptIndex
     *
     * @return string
     */
    public static function getScriptDigest(int $scriptIndex): string
    {
        assert(
            $scriptIndex === static::ADD_VALUE_SCRIPT_INDEX ||
            $scriptIndex === static::REMOVE_VALUE_SCRIPT_INDEX ||
            $scriptIndex === static::INVALIDATE_TAG_SCRIPT_INDEX
        );

        $sha1 = [
            static::ADD_VALUE_SCRIPT_INDEX      => '0aa6dbf4cc17ca0a261c4664ef6fe5fe9cd44fd0',
            static::REMOVE_VALUE_SCRIPT_INDEX   => 'bb69c1c4da3c4ec6afe545e893e5521bc2e7191e',
            static::INVALIDATE_TAG_SCRIPT_INDEX => '96181c1772cea8dd3a74a09d749ef20bb7f0349f',
        ][$scriptIndex];

        assert(
            $sha1 === sha1(static::getScriptBody($scriptIndex)),
            "Script body with ID `$scriptIndex` do not match expected sha1 " .
            sha1(static::getScriptBody($scriptIndex))
        );

        return $sha1;
    }
}
