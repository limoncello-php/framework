<?php declare(strict_types=1);

namespace Limoncello\Passport\Integration;

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

use Doctrine\DBAL\Connection;
use Exception;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\OAuthServer\Contracts\ClientInterface;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemaInterface;
use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Limoncello\Passport\Contracts\PassportServerIntegrationInterface;
use Limoncello\Passport\Entities\Client;
use Limoncello\Passport\Entities\DatabaseSchema;
use Limoncello\Passport\Package\PassportSettings as C;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Uri;
use function array_filter;
use function assert;
use function bin2hex;
use function call_user_func;
use function implode;
use function is_int;
use function is_string;
use function password_verify;
use function random_bytes;
use function uniqid;

/**
 * @package Limoncello\Passport
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class BasePassportServerIntegration implements PassportServerIntegrationInterface
{
    /** Approval parameter */
    const SCOPE_APPROVAL_TYPE = 'type';

    /** Approval parameter */
    const SCOPE_APPROVAL_CLIENT_ID = 'client_id';

    /** Approval parameter */
    const SCOPE_APPROVAL_CLIENT_NAME = 'client_name';

    /** Approval parameter */
    const SCOPE_APPROVAL_REDIRECT_URI = 'redirect_uri';

    /** Approval parameter */
    const SCOPE_APPROVAL_IS_SCOPE_MODIFIED = 'is_scope_modified';

    /** Approval parameter */
    const SCOPE_APPROVAL_SCOPE = 'scope';

    /** Approval parameter */
    const SCOPE_APPROVAL_STATE = 'state';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var string
     */
    private $defaultClientId;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var DatabaseSchemaInterface
     */
    private $databaseSchema;

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
     * @var callable|null
     */
    private $customPropProvider;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->settings  = $container->get(SettingsProviderInterface::class)->get(C::class);

        /** @var Connection $connection */
        $connection = $container->get(Connection::class);

        /** @var callable|null $customPropProvider */
        $customPropProvider = $this->settings[C::KEY_TOKEN_CUSTOM_PROPERTIES_PROVIDER] ?? null;
        $wrapper            = $customPropProvider !== null ?
            function (TokenInterface $token) use ($container, $customPropProvider): array {
                return call_user_func($customPropProvider, $container, $token);
            } : null;

        $this->defaultClientId     = $this->settings[C::KEY_DEFAULT_CLIENT_ID];
        $this->connection          = $connection;
        $this->approvalUriString   = $this->settings[C::KEY_APPROVAL_URI_STRING];
        $this->errorUriString      = $this->settings[C::KEY_ERROR_URI_STRING];
        $this->codeExpiration      = $this->settings[C::KEY_CODE_EXPIRATION_TIME_IN_SECONDS] ?? 600;
        $this->tokenExpiration     = $this->settings[C::KEY_TOKEN_EXPIRATION_TIME_IN_SECONDS] ?? 3600;
        $this->isRenewRefreshValue = $this->settings[C::KEY_RENEW_REFRESH_VALUE_ON_TOKEN_REFRESH] ?? false;
        $this->customPropProvider  = $wrapper;
    }

    /**
     * @inheritdoc
     */
    public function validateUserId(string $userName, string $password)
    {
        $validator    = $this->settings[C::KEY_USER_CREDENTIALS_VALIDATOR];
        $nullOrUserId = call_user_func($validator, $this->getContainer(), $userName, $password);

        return $nullOrUserId;
    }

    /**
     * @inheritdoc
     */
    public function verifyAllowedUserScope(int $userIdentity, array $scope = null): ?array
    {
        $validator   = $this->settings[C::KEY_USER_SCOPE_VALIDATOR];
        $nullOrScope = call_user_func($validator, $this->getContainer(), $userIdentity, $scope);

        return $nullOrScope;
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
     *
     * @throws Exception
     */
    public function generateCodeValue(TokenInterface $token): string
    {
        $codeValue = bin2hex(random_bytes(16)) . uniqid();

        assert(is_string($codeValue) === true && empty($codeValue) === false);

        return $codeValue;
    }

    /**
     * @inheritdoc
     *
     * @throws Exception
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
        string $type,
        ClientInterface $client,
        string $redirectUri = null,
        bool $isScopeModified = false,
        array $scopeList = null,
        string $state = null,
        array $extraParameters = []
    ): ResponseInterface {
        /** @var Client $client */
        assert($client instanceof Client);

        // TODO think if we can receive objects instead of individual properties
        $scopeList = empty($scopeList) === true ? null : implode(' ', $scopeList);
        $filtered  = array_filter([
            self::SCOPE_APPROVAL_TYPE              => $type,
            self::SCOPE_APPROVAL_CLIENT_ID         => $client->getIdentifier(),
            self::SCOPE_APPROVAL_CLIENT_NAME       => $client->getName(),
            self::SCOPE_APPROVAL_REDIRECT_URI      => $redirectUri,
            self::SCOPE_APPROVAL_IS_SCOPE_MODIFIED => $isScopeModified,
            self::SCOPE_APPROVAL_SCOPE             => $scopeList,
            self::SCOPE_APPROVAL_STATE             => $state,
        ], function ($value) {
            return $value !== null;
        });

        return new RedirectResponse($this->createRedirectUri($this->getApprovalUriString(), $filtered));
    }

    /**
     * @inheritdoc
     */
    public function verifyClientCredentials(ClientInterface $client, string $credentials): bool
    {
        /** @var \Limoncello\Passport\Contracts\Entities\ClientInterface $client */
        assert($client instanceof \Limoncello\Passport\Contracts\Entities\ClientInterface);

        return password_verify($credentials, $client->getCredentials());
    }

    /**
     * @inheritdoc
     */
    public function getBodyTokenExtraParameters(TokenInterface $token): array
    {
        return $this->customPropProvider !== null ? call_user_func($this->customPropProvider, $token) : [];
    }

    /**
     * @param string $uri
     * @param array  $data
     *
     * @return UriInterface
     */
    protected function createRedirectUri(string $uri, array $data): UriInterface
    {
        $query  = http_build_query($data, '', '&', PHP_QUERY_RFC3986);
        $result = (new Uri($uri))->withQuery($query);

        return $result;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return DatabaseSchemaInterface
     */
    protected function getDatabaseSchema(): DatabaseSchemaInterface
    {
        if ($this->databaseSchema === null) {
            $this->databaseSchema = new DatabaseSchema();
        }

        return $this->databaseSchema;
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
