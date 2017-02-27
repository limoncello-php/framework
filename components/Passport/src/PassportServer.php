<?php namespace Limoncello\Passport;

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

use Limoncello\OAuthServer\BaseAuthorizationServer;
use Limoncello\OAuthServer\Contracts\AuthorizationCodeInterface;
use Limoncello\OAuthServer\Contracts\ClientInterface;
use Limoncello\OAuthServer\Contracts\GrantTypes;
use Limoncello\OAuthServer\Contracts\ResponseTypes;
use Limoncello\OAuthServer\Exceptions\OAuthRedirectException;
use Limoncello\OAuthServer\Exceptions\OAuthTokenBodyException;
use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Limoncello\Passport\Contracts\PassportServerIntegrationInterface;
use Limoncello\Passport\Traits\BasicClientAuthenticationTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Uri;

/**
 * @package Limoncello\Passport
 */
class PassportServer extends BaseAuthorizationServer
{
    use BasicClientAuthenticationTrait;

    /**
     * @var PassportServerIntegrationInterface
     */
    private $integration;

    /**
     * @param PassportServerIntegrationInterface $integration
     */
    public function __construct(PassportServerIntegrationInterface $integration)
    {
        parent::__construct();

        $this->setIntegration($integration);
    }

    /**
     * @inheritdoc
     */
    public function postCreateToken(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $parameters          = $request->getParsedBody();
            $authenticatedClient = $this->authenticateClient($request, $this->getIntegration());

            switch ($this->getGrantType($parameters)) {
                case GrantTypes::AUTHORIZATION_CODE:
                    $response = $this->codeIssueToken($parameters, $authenticatedClient);
                    break;
                case GrantTypes::RESOURCE_OWNER_PASSWORD_CREDENTIALS:
                    $response = $this->passIssueToken($parameters, $authenticatedClient);
                    break;
                case GrantTypes::CLIENT_CREDENTIALS:
                    if ($authenticatedClient === null) {
                        throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_CLIENT);
                    }
                    $response = $this->clientIssueToken($parameters, $authenticatedClient);
                    break;
                default:
                    throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_UNSUPPORTED_GRANT_TYPE);
            }
        } catch (OAuthTokenBodyException $exception) {
            $response = $this->createBodyErrorResponse($exception);
        }

        return $response;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function createAuthorization(array $parameters): ResponseInterface
    {
        try {
            $client       = null;
            $redirectUri  = null;
            $responseType = $this->getResponseType($parameters);
            switch ($responseType) {
                case ResponseTypes::AUTHORIZATION_CODE:
                    $redirectUri  = $this->codeGetRedirectUri($parameters);
                    // check client identifier and redirect URI
                    $isInvalid =
                        ($clientId = $this->codeGetClientId($parameters)) === null ||
                        ($client = $this->getIntegration()->getClientRepository()->read($clientId)) === null ||
                        $this->isValidRedirectUri($client, $redirectUri) === false;
                    if ($isInvalid === true) {
                        $response = $this->getIntegration()->createInvalidClientAndRedirectUriErrorResponse();
                    } else {
                        $response = $this->codeAskResourceOwnerForApproval(
                            $parameters,
                            $client,
                            $redirectUri,
                            $this->getMaxStateLength()
                        );
                    }
                    break;
                case ResponseTypes::IMPLICIT:
                    $redirectUri  = $this->implicitGetRedirectUri($parameters);
                    // check client identifier and redirect URI
                    $isInvalid =
                        ($clientId = $this->implicitGetClientId($parameters)) === null ||
                        ($client = $this->getIntegration()->getClientRepository()->read($clientId)) === null ||
                        $this->isValidRedirectUri($client, $redirectUri) === false;
                    if ($isInvalid === true) {
                        $response = $this->getIntegration()->createInvalidClientAndRedirectUriErrorResponse();
                    } else {
                        $response = $this->implicitAskResourceOwnerForApproval(
                            $parameters,
                            $client,
                            $redirectUri,
                            $this->getMaxStateLength()
                        );
                    }
                    break;
                default:
                    throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_UNSUPPORTED_GRANT_TYPE);
            }
        } catch (OAuthRedirectException $exception) {
            $response = $this->createRedirectErrorResponse($exception);
        }

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function codeCreateAskResourceOwnerForApprovalResponse(
        ClientInterface $client,
        string $redirectUri = null,
        bool $isScopeModified = false,
        array $scopeList = null,
        string $state = null,
        array $extraParameters = []
    ): ResponseInterface {
        return $this->getIntegration()->createAskResourceOwnerForApprovalResponse(
            $client,
            $redirectUri,
            $isScopeModified,
            $scopeList,
            $state,
            $extraParameters
        );
    }

    /**
     * @inheritdoc
     */
    public function codeReadAuthenticationCode(string $code)
    {
        return $this->getIntegration()->getTokenRepository()
            ->readByCode($code, $this->getIntegration()->getCodeExpirationPeriod());
    }

    /**
     * @inheritdoc
     */
    public function codeCreateAccessTokenResponse(
        AuthorizationCodeInterface $code,
        array $extraParameters = []
    ): ResponseInterface {
        /** @var TokenInterface $code */
        assert($code instanceof TokenInterface);

        $clientIdentifier = $code->getClientIdentifier();
        $userIdentifier   = $code->getUserIdentifier();
        list($tokenValue, $tokenType, $tokenExpiresIn, $refreshValue) =
            $this->getIntegration()->generateTokenValues(
                $clientIdentifier,
                $userIdentifier,
                $code->isScopeModified(),
                $code->getScopeIdentifiers()
            );

        assert(is_string($tokenValue) === true && empty($tokenValue) === false);
        assert(is_string($tokenType) === true && empty($tokenType) === false);
        assert(is_int($tokenExpiresIn) === true && $tokenExpiresIn > 0);
        assert($refreshValue === null || (is_string($refreshValue) === true && empty($refreshValue) === false));

        $this->getIntegration()->getTokenRepository()
            ->assignValuesToCode($code->getIdentifier(), $tokenValue, $tokenType, $tokenExpiresIn, $refreshValue);

        $response = $this->createBodyTokenResponse(
            $tokenValue,
            $tokenType,
            $tokenExpiresIn,
            $refreshValue,
            $code->isScopeModified(),
            $code->getScopeIdentifiers()
        );

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function codeRevokeTokens(AuthorizationCodeInterface $code)
    {
        $token = $this->codeReadAuthenticationCode($code);

        if ($token !== null) {
            $this->getIntegration()->getTokenRepository()->disable($token->getIdentifier());
        }
    }

    /**
     * @inheritdoc
     */
    public function codeReadClient(string $identifier)
    {
        return $this->getIntegration()->getClientRepository()->read($identifier);
    }

    /** @noinspection PhpTooManyParametersInspection
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function implicitCreateAskResourceOwnerForApprovalResponse(
        ClientInterface $client,
        string $redirectUri = null,
        bool $isScopeModified = false,
        array $scopeList = null,
        string $state = null,
        array $extraParameters = []
    ): ResponseInterface {
        return $this->getIntegration()->createAskResourceOwnerForApprovalResponse(
            $client,
            $redirectUri,
            $isScopeModified,
            $scopeList,
            $state,
            $extraParameters
        );
    }

    /** @noinspection PhpTooManyParametersInspection
     * @inheritdoc
     */
    public function passValidateCredentialsAndCreateAccessTokenResponse(
        $userName,
        $password,
        ClientInterface $client = null,
        bool $isScopeModified = false,
        array $scope = null,
        array $extraParameters = []
    ): ResponseInterface {
        assert($client !== null);

        if (($userId = $this->getIntegration()->validateUserId($userName, $password)) === null) {
            throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_GRANT);
        }
        assert(is_int($userId) === true);

        list($tokenValue, $tokenType, $tokenExpiresIn, $refreshValue) =
            $this->getIntegration()->generateTokenValues($client->getIdentifier(), $userId, $isScopeModified, $scope);

        assert(is_string($tokenValue) === true && empty($tokenValue) === false);
        assert(is_string($tokenType) === true && empty($tokenType) === false);
        assert(is_int($tokenExpiresIn) === true && $tokenExpiresIn > 0);
        assert($refreshValue === null || (is_string($refreshValue) === true && empty($refreshValue) === false));

        $this->getIntegration()->getTokenRepository()
            ->createToken($client->getIdentifier(), $userId, $tokenValue, $tokenType, $refreshValue);

        $response = $this->createBodyTokenResponse(
            $tokenValue,
            $tokenType,
            $tokenExpiresIn,
            $refreshValue,
            $isScopeModified,
            $scope
        );

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function passReadDefaultClient(): ClientInterface
    {
        $defaultClientId = $this->getIntegration()->getDefaultClientIdentifier();

        assert(is_string($defaultClientId) === true && empty($defaultClientId) === false);

        $defaultClient   = $this->getIntegration()->getClientRepository()->read($defaultClientId);

        assert($defaultClient !== null);

        return $defaultClient;
    }

    /**
     * @inheritdoc
     */
    public function clientCreateAccessTokenResponse(
        ClientInterface $client,
        bool $isScopeModified,
        array $scope = null,
        array $extraParameters = []
    ): ResponseInterface {
        $userId = null;
        list($tokenValue, $tokenType, $tokenExpiresIn) =
            $this->getIntegration()->generateTokenValues($client->getIdentifier(), $userId, $isScopeModified, $scope);
        $refreshValue = null;

        assert(is_string($tokenValue) === true && empty($tokenValue) === false);
        assert(is_string($tokenType) === true && empty($tokenType) === false);
        assert(is_int($tokenExpiresIn) === true && $tokenExpiresIn > 0);

        // TODO userId is null though it's not possible input value
        $this->getIntegration()->getTokenRepository()
            ->createToken($client->getIdentifier(), $userId, $tokenValue, $tokenType, $refreshValue);

        $response = $this->createBodyTokenResponse(
            $tokenValue,
            $tokenType,
            $tokenExpiresIn,
            $refreshValue,
            $isScopeModified,
            $scope
        );

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string      $tokenValue
     * @param string      $tokenType
     * @param int         $tokenExpiresIn
     * @param string|null $refreshValue
     * @param bool        $isScopeModified
     * @param array|null  $scopeIdentifiers
     *
     * @return ResponseInterface
     */
    protected function createBodyTokenResponse(
        string $tokenValue,
        string $tokenType,
        int $tokenExpiresIn,
        string $refreshValue = null,
        bool $isScopeModified = false,
        array $scopeIdentifiers = null
    ): ResponseInterface {
        // for access token format @link https://tools.ietf.org/html/rfc6749#section-5.1
        $scopeList  = $scopeIdentifiers === null || $isScopeModified === false ? null : implode(' ', $scopeIdentifiers);
        $parameters = $this->filterNulls([
            'access_token'  => $tokenValue,
            'token_type'    => $tokenType,
            'expires_in'    => $tokenExpiresIn,
            'refresh_token' => $refreshValue,
            'scope'         => $scopeList,
        ]);

        $response = new JsonResponse($parameters, 200, [
            'Cache-Control' => 'no-store',
            'Pragma'        => 'no-cache'
        ]);

        return $response;
    }

    /**
     * @param OAuthTokenBodyException $exception
     *
     * @return ResponseInterface
     */
    protected function createBodyErrorResponse(OAuthTokenBodyException $exception): ResponseInterface
    {
        $array = $this->filterNulls([
            'error'             => $exception->getErrorCode(),
            'error_description' => $exception->getErrorDescription(),
            'error_uri'         => $this->getBodyErrorUri($exception),
        ]);

        $response = new JsonResponse($array, $exception->getHttpCode(), $exception->getHttpHeaders());

        return $response;
    }

    /**
     * @param OAuthRedirectException $exception
     *
     * @return ResponseInterface
     */
    protected function createRedirectErrorResponse(OAuthRedirectException $exception): ResponseInterface
    {
        $parameters = $this->filterNulls([
            'error'             => $exception->getErrorCode(),
            'error_description' => $exception->getErrorDescription(),
            'error_uri'         => $exception->getErrorUri(),
            'state'             => $exception->getState(),
        ]);

        $fragment = $this->encodeAsXWwwFormUrlencoded($parameters);
        $uri      = (new Uri($exception->getRedirectUri()))->withFragment($fragment);

        $response = new RedirectResponse($uri, 302, $exception->getHttpHeaders());

        return $response;
    }

    /**
     * @return PassportServerIntegrationInterface
     */
    protected function getIntegration(): PassportServerIntegrationInterface
    {
        return $this->integration;
    }

    /**
     * @param PassportServerIntegrationInterface $integration
     *
     * @return PassportServer
     */
    protected function setIntegration(PassportServerIntegrationInterface $integration): PassportServer
    {
        $this->integration = $integration;

        return $this;
    }

    /**
     * @param OAuthTokenBodyException $exception
     *
     * @return null|string
     */
    protected function getBodyErrorUri(OAuthTokenBodyException $exception)
    {
        assert($exception !== null);

        return null;
    }

    /**
     * @param array $array
     *
     * @return array
     */
    private function filterNulls(array $array): array
    {
        return array_filter($array, function ($value) {
            return $value !== null;
        });
    }
}
