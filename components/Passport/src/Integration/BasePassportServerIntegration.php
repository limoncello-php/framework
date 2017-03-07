<?php namespace Limoncello\Passport\Integration;

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

use Doctrine\DBAL\Connection;
use Limoncello\OAuthServer\Contracts\ClientInterface;
use Limoncello\Passport\Adaptors\Generic\ClientRepository;
use Limoncello\Passport\Adaptors\Generic\TokenRepository;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Limoncello\Passport\Contracts\PassportServerIntegrationInterface;
use Limoncello\Passport\Contracts\Repositories\ClientRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\TokenRepositoryInterface;
use Limoncello\Passport\Entities\Client;
use Limoncello\Passport\Entities\DatabaseScheme;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Uri;

/**
 * @package Limoncello\Passport
 */
abstract class BasePassportServerIntegration implements PassportServerIntegrationInterface
{
    /**
     * @var string
     */
    private $defaultClientId;

    /**
     * @var ClientRepositoryInterface|null
     */
    private $clientRepo;

    /**
     * @var TokenRepositoryInterface|null
     */
    private $tokenRepo;


    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var DatabaseSchemeInterface
     */
    private $databaseScheme;

    /**
     * @var string
     */
    private $approvalUriString;

    /**
     * @var string
     */
    private $errorUriString;

    /**
     * @var int
     */
    private $codeExpiration;

    /**
     * @var int
     */
    private $tokenExpiration;
    /**
     * @var bool
     */
    private $isRenewRefreshValue;

    /**
     * @param Connection $connection
     * @param string     $defaultClientId
     * @param string     $approvalUriString
     * @param string     $errorUriString
     * @param int        $codeExpiration
     * @param int        $tokenExpiration
     * @param bool       $isRenewRefreshValue
     */
    public function __construct(
        Connection $connection,
        string $defaultClientId,
        string $approvalUriString,
        string $errorUriString,
        int $codeExpiration = 600,
        int $tokenExpiration = 3600,
        bool $isRenewRefreshValue = false
    ) {
        $this->defaultClientId     = $defaultClientId;
        $this->connection          = $connection;
        $this->approvalUriString   = $approvalUriString;
        $this->errorUriString      = $errorUriString;
        $this->codeExpiration      = $codeExpiration;
        $this->tokenExpiration     = $tokenExpiration;
        $this->isRenewRefreshValue = $isRenewRefreshValue;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultClientIdentifier(): string
    {
        return $this->defaultClientId;
    }

    /**
     * @inheritdoc
     */
    public function getClientRepository(): ClientRepositoryInterface
    {
        if ($this->clientRepo === null) {
            $this->clientRepo = new ClientRepository($this->getConnection(), $this->getDatabaseScheme());
        }

        return $this->clientRepo;
    }

    /**
     * @inheritdoc
     */
    public function getTokenRepository(): TokenRepositoryInterface
    {
        if ($this->tokenRepo === null) {
            $this->tokenRepo = new TokenRepository($this->getConnection(), $this->getDatabaseScheme());
        }

        return $this->tokenRepo;
    }

    /**
     * @inheritdoc
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * @inheritdoc
     */
    public function generateCodeValue(TokenInterface $token): string
    {
        $codeValue = bin2hex(random_bytes(16)) . uniqid();

        assert(is_string($codeValue) === true && empty($codeValue) === false);

        return $codeValue;
    }

    /**
     * @inheritdoc
     */
    public function generateTokenValues(TokenInterface $token): array
    {
        $tokenValue     = bin2hex(random_bytes(16)) . uniqid();
        $tokenType      = 'bearer';
        $tokenExpiresIn = $this->getTokenExpirationPeriod();
        $refreshValue   = bin2hex(random_bytes(16)) . uniqid();

        assert(is_string($tokenValue) === true && empty($tokenValue) === false);
        assert(is_string($tokenType) === true && empty($tokenType) === false);
        assert(is_int($tokenExpiresIn) === true && $tokenExpiresIn > 0);
        assert($refreshValue === null || (is_string($refreshValue) === true && empty($refreshValue) === false));

        return [$tokenValue, $tokenType, $tokenExpiresIn, $refreshValue];
    }

    /**
     * @inheritdoc
     */
    public function getCodeExpirationPeriod(): int
    {
        return $this->codeExpiration;
    }

    /**
     * @inheritdoc
     */
    public function getTokenExpirationPeriod(): int
    {
        return $this->tokenExpiration;
    }

    /**
     * @inheritdoc
     */
    public function isRenewRefreshValue(): bool
    {
        return $this->isRenewRefreshValue;
    }

    /**
     * @inheritdoc
     */
    public function createInvalidClientAndRedirectUriErrorResponse(): ResponseInterface
    {
        return new RedirectResponse($this->getErrorUriString());
    }

    /** @noinspection PhpTooManyParametersInspection
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function createAskResourceOwnerForApprovalResponse(
        ClientInterface $client,
        string $redirectUri = null,
        bool $isScopeModified = false,
        array $scopeList = null,
        string $state = null,
        array $extraParameters = []
    ): ResponseInterface {
        /** @var Client $client */
        assert($client instanceof Client);

        $filtered = array_filter([

            // TODO move strings to constants and add require approval URI have no fragment


            'client_id'         => $client->getIdentifier(),
            'client_name'       => $client->getName(),
            'redirect_uri'      => $redirectUri,
            'is_scope_modified' => $isScopeModified,
            'scope'             => $scopeList === null ? null : implode(' ', $scopeList),
            'state'             => $state,

        ], function ($value) {
            return $value !== null;
        });

        /** @var Client $client */
        $fragment = http_build_query($filtered, '', '&', PHP_QUERY_RFC3986);
        $uri      = (new Uri($this->getApprovalUriString()))->withFragment($fragment);

        return new RedirectResponse($uri);
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return DatabaseSchemeInterface
     */
    protected function getDatabaseScheme(): DatabaseSchemeInterface
    {
        if ($this->databaseScheme === null) {
            $this->databaseScheme = new DatabaseScheme();
        }

        return $this->databaseScheme;
    }

    /**
     * @return string
     */
    protected function getApprovalUriString(): string
    {
        return $this->approvalUriString;
    }

    /**
     * @return string
     */
    protected function getErrorUriString(): string
    {
        return $this->errorUriString;
    }
}
