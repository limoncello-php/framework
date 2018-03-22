<?php namespace Limoncello\Tests\Flute\Http\Traits;

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

use Exception;
use Limoncello\Contracts\Routing\GroupInterface;
use Limoncello\Flute\Http\Traits\FluteRoutesTrait;
use Limoncello\Tests\Flute\Data\Http\ApiCategoriesController;
use Limoncello\Tests\Flute\Data\Schemas\CategorySchema;
use Limoncello\Tests\Flute\TestCase;
use Mockery;
use Mockery\Mock;

/**
 * @package Limoncello\Tests\Flute
 */
class FluteRoutesTraitTest extends TestCase
{
    use FluteRoutesTrait;

    /**
     * Test helper method.
     *
     * @throws Exception
     */
    public function testControllerMethod(): void
    {
        /** @var Mock $group */
        $group = Mockery::mock(GroupInterface::class);

        $group->shouldReceive('get')->twice()->withAnyArgs()->andReturnSelf();
        $group->shouldReceive('post')->times(3)->withAnyArgs()->andReturnSelf();

        /** @var GroupInterface $group */

        $this->controller($group, '/categories', ApiCategoriesController::class);

        // mockery will do checks when the test finished
        $this->assertTrue(true);
    }

    /**
     * Test helper method.
     *
     * @throws Exception
     */
    public function testResourceMethod(): void
    {
        /** @var Mock $group */
        $group = Mockery::mock(GroupInterface::class);

        $group->shouldReceive('get')->twice()->withAnyArgs()->andReturnSelf();
        $group->shouldReceive('post')->once()->withAnyArgs()->andReturnSelf();
        $group->shouldReceive('patch')->once()->withAnyArgs()->andReturnSelf();
        $group->shouldReceive('delete')->once()->withAnyArgs()->andReturnSelf();

        /** @var GroupInterface $group */

        $this->resource($group, CategorySchema::TYPE, ApiCategoriesController::class);

        // mockery will do checks when the test finished
        $this->assertTrue(true);
    }

    /**
     * Test helper method.
     *
     * @throws Exception
     */
    public function testRelationshipMethod(): void
    {
        /** @var Mock $group */
        $group = Mockery::mock(GroupInterface::class);

        $group->shouldReceive('get')->twice()->withAnyArgs()->andReturnSelf();

        /** @var GroupInterface $group */

        $this->relationship(
            $group,
            CategorySchema::TYPE,
            CategorySchema::REL_CHILDREN,
            ApiCategoriesController::class,
            'readChildren'
        );

        // mockery will do checks when the test finished
        $this->assertTrue(true);
    }
}
