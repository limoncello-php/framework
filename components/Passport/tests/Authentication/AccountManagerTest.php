<?php namespace Limoncello\Tests\Passport\Authentication;

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

use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Passport\Authentication\AccountManager;
use Limoncello\Contracts\Authentication\AccountInterface;
use Limoncello\Contracts\Authentication\AccountManagerInterface;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use Limoncello\Passport\Contracts\Repositories\TokenRepositoryInterface;
use Limoncello\Passport\Entities\DatabaseScheme;
use Limoncello\Passport\Package\PassportSettings;
use Limoncello\Tests\Passport\Data\TestContainer;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Passport
 */
class AccountManagerTest extends TestCase
{
    /**
     * Test get and set.
     */
    public function testGetSet()
    {
        $container = new TestContainer();

        /** @var AccountManagerInterface $manager */
        $manager = new AccountManager($container);
        $this->assertNull($manager->getAccount());

        /** @var AccountInterface $mockAccount */
        $mockAccount = Mockery::mock(AccountInterface::class);
        $this->assertSame($mockAccount, $manager->setAccount($mockAccount)->getAccount());
    }

    /**
     * Test setting current account with token value.
     */
    public function testSetAccountWithTokenValue()
    {
        $container = new TestContainer();

        /** @var Mock $repoMock */
        /** @var Mock $providerMock */
        $container[TokenRepositoryInterface::class] = $repoMock = Mockery::mock(TokenRepositoryInterface::class);
        $container[SettingsProviderInterface::class] = $providerMock = Mockery::mock(SettingsProviderInterface::class);
        $container[DatabaseSchemeInterface::class] = $scheme = new DatabaseScheme();

        $timeout    = 3600;
        $tokenValue = '123';

        $providerMock->shouldReceive('get')->once()->with(PassportSettings::class)->andReturn([
            PassportSettings::KEY_TOKEN_EXPIRATION_TIME_IN_SECONDS => $timeout,
        ]);

        $properties = [
            $scheme->getTokensUserIdentityColumn()   => $userId = '123',
            $scheme->getTokensClientIdentityColumn() => $clientId = 'some_client_id',
            $scheme->getTokensViewScopesColumn()     => [
                $scope1 = 'some_scope_1',
            ],
        ];
        $repoMock->shouldReceive('readPassport')->once()->with($tokenValue, $timeout)->andReturn($properties);

        $account = (new AccountManager($container))->setAccountWithTokenValue($tokenValue);

        $this->assertTrue($account->hasUserIdentity());
        $this->assertEquals($userId, $account->getUserIdentity());
        $this->assertTrue($account->hasClientIdentity());
        $this->assertEquals($clientId, $account->getClientIdentity());
        $this->assertTrue($account->hasScope($scope1));
        $this->assertFalse($account->hasScope($scope1 . 'XXX'));
    }

    /**
     * Test setting current account with invalid token value.
     *
     * @expectedException \Limoncello\Passport\Exceptions\InvalidArgumentException
     */
    public function testSetAccountWithInvalidTokenValue()
    {
        $container = new TestContainer();

        /** @var Mock $repoMock */
        /** @var Mock $providerMock */
        $container[TokenRepositoryInterface::class] = $repoMock = Mockery::mock(TokenRepositoryInterface::class);
        $container[SettingsProviderInterface::class] = $providerMock = Mockery::mock(SettingsProviderInterface::class);

        $timeout    = 3600;
        $tokenValue = '123';

        $providerMock->shouldReceive('get')->once()->with(PassportSettings::class)->andReturn([
            PassportSettings::KEY_TOKEN_EXPIRATION_TIME_IN_SECONDS => $timeout,
        ]);

        $properties = null;
        $repoMock->shouldReceive('readPassport')->once()->with($tokenValue, $timeout)->andReturn($properties);

        (new AccountManager($container))->setAccountWithTokenValue($tokenValue);
    }
}
