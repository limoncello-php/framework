<?php declare(strict_types=1);

namespace Limoncello\RedisTaggedCache;

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

use Limoncello\RedisTaggedCache\Exceptions\RedisTaggedCacheException;
use Limoncello\RedisTaggedCache\Scripts\RedisTaggedScripts;
use Redis;
use RuntimeException;
use function assert;
use function json_encode;

/**
 * @package Limoncello\RedisTaggedCache
 */
trait RedisTaggedCacheTrait
{
    /**
     * @var Redis
     */
    private $redisInstance;

    /**
     * @var string
     */
    private $internalKeysPrefix = '_:k:';

    /**
     * @var string
     */
    private $internalTagsPrefix = '_:t:';
    /**
     * @return Redis
     */
    protected function getRedisInstance(): Redis
    {
        assert($this->redisInstance !== null, 'Redis instance should be set before usage.');

        return $this->redisInstance;
    }

    /**
     * @param Redis $redisInstance
     */
    protected function setRedisInstance(Redis $redisInstance): void
    {
        $this->redisInstance = $redisInstance;
    }

    /** @noinspection PhpDocRedundantThrowsInspection
     * @param string $key
     * @param string $value
     * @param array  $tags
     * @param int    $ttl
     *
     * @return void
     *
     * @throws RedisTaggedCacheException
     */
    public function addTaggedValue(string $key, string $value, array $tags, $ttl = 0): void
    {
        $jsonTags = json_encode($tags);
        $isOk     = $this->evalScript(
            RedisTaggedScripts::ADD_VALUE_SCRIPT_INDEX,
            [$key, $value, $jsonTags, $this->getInternalKeysPrefix(), $this->getInternalTagsPrefix(), $ttl],
            1
        );

        if ($isOk === false) {
            throw new class extends RuntimeException implements RedisTaggedCacheException
            {
            };
        }
    }

    /** @noinspection PhpDocRedundantThrowsInspection
     * @param string $key
     *
     * @return void
     *
     * @throws RedisTaggedCacheException
     */
    public function removeTaggedValue(string $key): void
    {
        $isOk = $this->evalScript(
            RedisTaggedScripts::REMOVE_VALUE_SCRIPT_INDEX,
            [$key, $this->getInternalKeysPrefix(), $this->getInternalTagsPrefix()],
            1
        );

        if ($isOk === false) {
            throw new class extends RuntimeException implements RedisTaggedCacheException
            {
            };
        }
    }

    /** @noinspection PhpDocRedundantThrowsInspection
     * @param string $tag
     *
     * @return void
     *
     * @throws RedisTaggedCacheException
     */
    public function invalidateTag(string $tag): void
    {
        $isOk = $this->evalScript(
            RedisTaggedScripts::INVALIDATE_TAG_SCRIPT_INDEX,
            [$tag, $this->getInternalKeysPrefix(), $this->getInternalTagsPrefix()],
            1
        );

        if ($isOk === false) {
            throw new class extends RuntimeException implements RedisTaggedCacheException
            {
            };
        }
    }

    /**
     * @param int   $scriptIndex
     * @param array $arguments
     * @param int   $keysInArgs
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function evalScript(int $scriptIndex, array $arguments, int $keysInArgs): bool
    {
        $retValue = $this->getRedisInstance()
            ->evalSha(RedisTaggedScripts::getScriptDigest($scriptIndex), $arguments, $keysInArgs);

        if ($retValue === false) {
            // script not loaded yet
            $script = RedisTaggedScripts::getScriptBody($scriptIndex);
            $digest = $this->getRedisInstance()->script('load', $script);
            assert($digest === RedisTaggedScripts::getScriptDigest($scriptIndex));

            // eval one more time
            $retValue = $this->getRedisInstance()
                ->evalSha(RedisTaggedScripts::getScriptDigest($scriptIndex), $arguments, $keysInArgs);
        }

        return $retValue === 0;
    }

    /**
     * @return string
     */
    protected function getInternalKeysPrefix(): string
    {
        return $this->internalKeysPrefix;
    }

    /**
     * @param string $internalKeysPrefix
     */
    protected function setInternalKeysPrefix(string $internalKeysPrefix): void
    {
        $this->internalKeysPrefix = $internalKeysPrefix;
    }

    /**
     * @return string
     */
    protected function getInternalTagsPrefix(): string
    {
        return $this->internalTagsPrefix;
    }

    /**
     * @param string $internalTagsPrefix
     */
    protected function setInternalTagsPrefix(string $internalTagsPrefix): void
    {
        $this->internalTagsPrefix = $internalTagsPrefix;
    }
}
