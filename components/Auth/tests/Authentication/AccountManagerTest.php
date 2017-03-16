<?php namespace Limoncello\Tests\Auth\Authentication;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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

use Limoncello\Auth\Authentication\AccountManager;
use Limoncello\Auth\Contracts\Authentication\AccountInterface;
use Limoncello\Auth\Contracts\Authentication\AccountManagerInterface;
use Mockery;

/**
 * @package Limoncello\Tests\Auth
 */
class AccountManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test get and set.
     */
    public function testGetSet()
    {
        /** @var AccountInterface $mock */
        $mock    = Mockery::mock(AccountInterface::class);
        /** @var AccountManagerInterface $manager */
        $manager = new AccountManager();

        $this->assertNull($manager->getAccount());
        $this->assertSame($mock, $manager->setAccount($mock)->getAccount());
    }
}
