<?php declare(strict_types=1);

namespace Limoncello\Tests\RedisTaggedCache;

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
use Limoncello\RedisTaggedCache\RedisTaggedCacheTrait;
use Limoncello\RedisTaggedCache\Scripts\RedisTaggedScripts;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Redis;

/**
 * @package Limoncello\Tests\RedisTaggedCache
 */
class RedisTaggedCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @return void
     */
    public function testAddTaggedValueSuccessfully(): void
    {
        $cache = $this->createCacheWithSuccessEmulation(RedisTaggedScripts::ADD_VALUE_SCRIPT_INDEX);
        $cache->addTaggedValue('key', 'value', ['author:1', 'comment:2']);

        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testAddTaggedValueUnsuccessfully(): void
    {
        $cache = $this->createCacheWithFailEmulation(RedisTaggedScripts::ADD_VALUE_SCRIPT_INDEX);

        $this->expectException(RedisTaggedCacheException::class);

        $cache->addTaggedValue('key', 'value', ['author:1', 'comment:2']);
    }

    /**
     * @return void
     */
    public function testRemoveTaggedValueSuccessfully(): void
    {
        $cache = $this->createCacheWithSuccessEmulation(RedisTaggedScripts::REMOVE_VALUE_SCRIPT_INDEX);
        $cache->removeTaggedValue('key');

        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testRemoveTaggedValueUnsuccessfully(): void
    {
        $cache = $this->createCacheWithFailEmulation(RedisTaggedScripts::REMOVE_VALUE_SCRIPT_INDEX);

        $this->expectException(RedisTaggedCacheException::class);

        $cache->removeTaggedValue('key');
    }

    /**
     * @return void
     */
    public function testInvalidateTagSuccessfully(): void
    {
        $cache = $this->createCacheWithSuccessEmulation(RedisTaggedScripts::INVALIDATE_TAG_SCRIPT_INDEX);
        $cache->invalidateTag('tag');

        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testInvalidateTagUnsuccessfully(): void
    {
        $cache = $this->createCacheWithFailEmulation(RedisTaggedScripts::INVALIDATE_TAG_SCRIPT_INDEX);

        $this->expectException(RedisTaggedCacheException::class);

        $cache->invalidateTag('tag');
    }

    /**
     * @param int $scriptId
     *
     * @return mixed
     */
    private function createCacheWithSuccessEmulation(int $scriptId)
    {
        /** @var MockInterface $redis */
        [$cache, $redis] = $this->createCache();

        $redis->shouldReceive('evalsha')->once()->withAnyArgs()->andReturn(false);
        $redis->shouldReceive('script')->once()->withAnyArgs()
            ->andReturn(RedisTaggedScripts::getScriptDigest($scriptId));
        $redis->shouldReceive('evalsha')->once()->withAnyArgs()->andReturn(0);

        return $cache;
    }

    /**
     * @param int $scriptId
     *
     * @return mixed
     */
    private function createCacheWithFailEmulation(int $scriptId)
    {
        /** @var MockInterface $redis */
        [$cache, $redis] = $this->createCache();

        $redis->shouldReceive('evalsha')->twice()->withAnyArgs()->andReturn(false);
        $redis->shouldReceive('script')->once()->withAnyArgs()
            ->andReturn(RedisTaggedScripts::getScriptDigest($scriptId));

        return $cache;
    }

    /**
     * @return array [cache, redis mock]
     */
    private function createCache(): array
    {
        $redis = Mockery::mock(Redis::class);

        /** @var Redis $redis */

        $cache = new class ($redis)
        {
            use RedisTaggedCacheTrait;

            /**
             * @param Redis $redis
             */
            public function __construct(Redis $redis)
            {
                $this->setRedisInstance($redis);

                // add some coverage to config methods
                $this->setInternalKeysPrefix('_:' . $this->getInternalKeysPrefix());
                $this->setInternalTagsPrefix('_:' . $this->getInternalTagsPrefix());
            }
        };

        return [$cache, $redis];
    }
}
