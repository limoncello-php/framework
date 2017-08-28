<?php namespace Limoncello\Tests\Flute\Adapters;

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

use Limoncello\Flute\Adapters\PaginationStrategy as PS;
use Limoncello\Tests\Flute\TestCase;

/**
 * @package Limoncello\Tests\Flute
 */
class PaginationStrategyTest extends TestCase
{
    /**
     * Test parse input paging parameters.
     */
    public function testParsingWithDefaultLessThanMaxLimitSize(): void
    {
        $this->assertLessThan(PS::MAX_LIMIT_SIZE, $defaultPageSize = 30);
        $strategy = new PS($defaultPageSize);

        $skip = 0;
        $this->assertLessThan(PS::MAX_LIMIT_SIZE, $size = 40);
        $parsed = $strategy->parseParameters([PS::PARAM_PAGING_SKIP => $skip, PS::PARAM_PAGING_SIZE => $size]);
        $this->assertEquals([$skip, $size + 1], $parsed);

        $skip = -1;
        $this->assertLessThan(PS::MAX_LIMIT_SIZE, $size = 40);
        $parsed = $strategy->parseParameters([PS::PARAM_PAGING_SKIP => $skip, PS::PARAM_PAGING_SIZE => $size]);
        $this->assertEquals([0, $size + 1], $parsed);

        $this->assertGreaterThan(PS::MAX_LIMIT_SIZE, $skip = 200);
        $this->assertLessThan(PS::MAX_LIMIT_SIZE, $size = 40);
        $parsed = $strategy->parseParameters([PS::PARAM_PAGING_SKIP => $skip, PS::PARAM_PAGING_SIZE => $size]);
        $this->assertEquals([$skip, $size + 1], $parsed);

        $skip = 0;
        $this->assertGreaterThan(PS::MAX_LIMIT_SIZE, $size = 200);
        $parsed = $strategy->parseParameters([PS::PARAM_PAGING_SKIP => $skip, PS::PARAM_PAGING_SIZE => $size]);
        $this->assertEquals([$skip, PS::MAX_LIMIT_SIZE + 1], $parsed);

        $skip = 0;
        $size = -200;
        $parsed = $strategy->parseParameters([PS::PARAM_PAGING_SKIP => $skip, PS::PARAM_PAGING_SIZE => $size]);
        $this->assertEquals([$skip, 1 + 1], $parsed);
    }

    /**
     * Test parse input paging parameters.
     */
    public function testParsingWithDefaultGreaterThanMaxLimitSize(): void
    {
        $this->assertGreaterThan(PS::MAX_LIMIT_SIZE, $defaultPageSize = 200);
        $strategy = new PS($defaultPageSize);

        $skip = 0;
        $this->assertLessThan(PS::MAX_LIMIT_SIZE, $size = 40);
        $parsed = $strategy->parseParameters([PS::PARAM_PAGING_SKIP => $skip, PS::PARAM_PAGING_SIZE => $size]);
        $this->assertEquals([$skip, $size + 1], $parsed);

        $skip = -1;
        $this->assertLessThan(PS::MAX_LIMIT_SIZE, $size = 40);
        $parsed = $strategy->parseParameters([PS::PARAM_PAGING_SKIP => $skip, PS::PARAM_PAGING_SIZE => $size]);
        $this->assertEquals([0, $size + 1], $parsed);

        $this->assertGreaterThan(PS::MAX_LIMIT_SIZE, $skip = 200);
        $this->assertLessThan(PS::MAX_LIMIT_SIZE, $size = 40);
        $parsed = $strategy->parseParameters([PS::PARAM_PAGING_SKIP => $skip, PS::PARAM_PAGING_SIZE => $size]);
        $this->assertEquals([$skip, $size + 1], $parsed);

        $skip = 0;
        $this->assertGreaterThan(PS::MAX_LIMIT_SIZE, $size = 200);
        $parsed = $strategy->parseParameters([PS::PARAM_PAGING_SKIP => $skip, PS::PARAM_PAGING_SIZE => $size]);
        $this->assertEquals([$skip, $defaultPageSize + 1], $parsed);
    }
}
