<?php namespace Limoncello\Passport\Authentication;

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

use Limoncello\Contracts\Authentication\AccountInterface;
use Limoncello\Contracts\Authentication\AccountManagerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Passport\Contracts\Authentication\PassportAccountInterface;
use Limoncello\Passport\Contracts\Authentication\PassportAccountManagerInterface;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use Limoncello\Passport\Contracts\Repositories\TokenRepositoryInterface;
use Limoncello\Passport\Exceptions\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Limoncello\Passport\Package\PassportSettings as S;

/**
 * @package Limoncello\Passport
 */
class AccountManager implements PassportAccountManagerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var null|PassportAccountInterface
     */
    private $account = null;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function getAccount()
    {
        return $this->getPassport();
    }

    /**
     * @inheritdoc
     */
    public function getPassport()
    {
        return $this->account;
    }

    /**
     * @inheritdoc
     */
    public function setAccount(AccountInterface $account): AccountManagerInterface
    {
        assert($account instanceof PassportAccountInterface);

        $this->account = $account;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAccountWithTokenValue(string $value): PassportAccountInterface
    {
        /** @var TokenRepositoryInterface $tokenRepo */
        $tokenRepo    = $this->getContainer()->get(TokenRepositoryInterface::class);
        $expInSeconds = $this->getPassportSettings()[S::KEY_TOKEN_EXPIRATION_TIME_IN_SECONDS];
        $properties   = $tokenRepo->readPassport($value, $expInSeconds);
        if ($properties === null) {
            throw new InvalidArgumentException($value);
        }

        /** @var DatabaseSchemeInterface $scheme */
        $scheme  = $this->getContainer()->get(DatabaseSchemeInterface::class);
        $account = new PassportAccount($scheme, $properties);
        $this->setAccount($account);

        return $account;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return array
     */
    protected function getPassportSettings(): array
    {
        $settings = $this->getContainer()->get(SettingsProviderInterface::class)->get(S::class);

        return $settings;
    }
}
