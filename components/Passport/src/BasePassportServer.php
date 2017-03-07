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
use Limoncello\Passport\Contracts\PassportServerInterface;
use Limoncello\Passport\Entities\Token;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Uri;

/**
 * @package Limoncello\Passport
 */
abstract class BasePassportServer extends BaseAuthorizationServer implements PassportServerInterface
{
    /**
     * @param PassportServerIntegrationInterface $integration
     * @param ServerRequestInterface             $request
     * @param array                              $parameters
     * @param string                             $realm
     *
     * @return ClientInterface|null
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    abstract protected function determineClient(
        PassportServerIntegrationInterface $integration,
        ServerRequestInterface $request,
        array $parameters,
        $realm = 'OAuth'
    );

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
                    list($client, $redirectUri) = $this->getValidClientAndRedirectUri(
                        $this->codeGetClientId($parameters),
                        $this->codeGetRedirectUri($parameters)
                    );
                    $response = $client === null || $redirectUri === null ?
                        $this->getIntegration()->createInvalidClientAndRedirectUriErrorResponse() :
                        $this->codeAskResourceOwnerForApproval(
                            $parameters,
                            $client,
                            $redirectUri,
                            $this->getMaxStateLength()
                        );
                    break;
                case ResponseTypes::IMPLICIT:
                    list($client, $redirectUri) = $this->getValidClientAndRedirectUri(
                        $this->implicitGetClientId($parameters),
                        $this->implicitGetRedirectUri($parameters)
                    );
                    $response = $client === null || $redirectUri === null ?
                        $this->getIntegration()->createInvalidClientAndRedirectUriErrorResponse() :
                        $this->implicitAskResourceOwnerForApproval(
                            $parameters,
                            $client,
                            $redirectUri,
                            $this->getMaxStateLength()
                        );
                    break;
                default:
                    throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_UNSUPPORTED_GRANT_TYPE);
            }
        } catch (OAuthRedirectException $exception) {
            $response = $this->createRedirectErrorResponse($exception);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function postCreateToken(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $parameters       = $request->getParsedBody();
            $determinedClient = $this->determineClient($this->getIntegration(), $request, $parameters);

            switch ($this->getGrantType($parameters)) {
                case GrantTypes::AUTHORIZATION_CODE:
                    $response = $this->codeIssueToken($parameters, $determinedClient);
                    break;
                case GrantTypes::RESOURCE_OWNER_PASSWORD_CREDENTIALS:
                    $response = $this->passIssueToken($parameters, $determinedClient);
                    break;
                case GrantTypes::CLIENT_CREDENTIALS:
                    if ($determinedClient === null) {
                        throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_CLIENT);
                    }
                    $response = $this->clientIssueToken($parameters, $determinedClient);
                    break;
                case GrantTypes::REFRESH_TOKEN:
                    if ($determinedClient === null) {
                        throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_CLIENT);
                    }
                    $response = $this->refreshIssueToken($parameters, $determinedClient);
                    break;
                default:
                    throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_UNSUPPORTED_GRANT_TYPE);
            }
        } catch (OAuthTokenBodyException $exception) {
            $response = $this->createBodyErrorResponse($exception);
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
        /** @var Token $code */
        assert($code instanceof Token);
        $updatedToken = clone $code;

        $tokenExpiresIn = $this->setUpTokenValues($updatedToken);
        $this->getIntegration()->getTokenRepository()->assignValuesToCode($updatedToken, $tokenExpiresIn);

        $response = $this->createBodyTokenResponse($updatedToken, $tokenExpiresIn);

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

        if (($userIdentifier = $this->getIntegration()->validateUserId($userName, $password)) === null) {
            throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_GRANT);
        }
        assert(is_int($userIdentifier) === true);

        // TODO would be nice to have created token as input so it's not here to decide which token class to use
        $unsavedToken = (new \Limoncello\Passport\Adaptors\Generic\Token())
            ->setClientIdentifier($client->getIdentifier())
            ->setUserIdentifier($userIdentifier);
        if ($isScopeModified === true && empty($scope) === false) {
            $unsavedToken->setScopeModified()->setTokenScopeStrings($scope);
        }

        $tokenExpiresIn = $this->setUpTokenValues($unsavedToken);
        $savedToken     = $this->getIntegration()->getTokenRepository()->createToken($unsavedToken);

        $response = $this->createBodyTokenResponse($savedToken, $tokenExpiresIn);

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
        assert($client !== null);

        // TODO would be nice to have created token as input so it's not here to decide which token class to use
        $unsavedToken = (new \Limoncello\Passport\Adaptors\Generic\Token())
            ->setClientIdentifier($client->getIdentifier());
        if ($isScopeModified === true && empty($scope) === false) {
            $unsavedToken->setScopeModified()->setTokenScopeStrings($scope);
        }

        $tokenExpiresIn = $this->setUpTokenValue($unsavedToken);
        $savedToken     = $this->getIntegration()->getTokenRepository()->createToken($unsavedToken);

        $response = $this->createBodyTokenResponse($savedToken, $tokenExpiresIn);

        return $response;
    }

    /**
     * @inheritdoc
     *
     * @return TokenInterface|null
     */
    public function readTokenByRefreshValue(string $refreshValue)
    {
        return $this->getIntegration()->getTokenRepository()->readByRefresh(
            $refreshValue,
            $this->getIntegration()->getTokenExpirationPeriod()
        );
    }

    /**
     * @inheritdoc
     */
    public function refreshCreateAccessTokenResponse(
        ClientInterface $client,
        \Limoncello\OAuthServer\Contracts\TokenInterface $token,
        bool $isScopeModified,
        array $scope = null,
        array $extraParameters = []
    ): ResponseInterface {
        /** @var Token $token */
        assert($token instanceof Token);

        $updatedToken   = clone $token;
        $tokenExpiresIn = $this->getIntegration()->isRenewRefreshValue() === false ?
            $this->setUpTokenValue($updatedToken) : $this->setUpTokenValues($updatedToken);

        $tokenRepo = $this->getIntegration()->getTokenRepository();
        if ($isScopeModified === false) {
            $tokenRepo->updateValues($updatedToken);
        } else {
            assert(is_array($scope));
            $tokenRepo->inTransaction(function () use ($tokenRepo, $updatedToken, $scope) {
                $tokenRepo->updateValues($updatedToken);
                $tokenRepo->unbindScopes($updatedToken->getIdentifier());
                $tokenRepo->bindScopeIdentifiers($updatedToken->getIdentifier(), $scope);
            });
            $updatedToken->setScopeModified()->setTokenScopeStrings($scope);
        }
        $response = $this->createBodyTokenResponse($updatedToken, $tokenExpiresIn);

        return $response;
    }

    /**
     * @param string|null $clientId
     * @param string|null $redirectFromQuery
     *
     * @return array [client|null, uri|null]
     */
    protected function getValidClientAndRedirectUri(string $clientId = null, string $redirectFromQuery = null)
    {
        $client           = null;
        $validRedirectUri = null;

        if ($clientId !== null &&
            ($client = $this->getIntegration()->getClientRepository()->read($clientId)) !== null
        ) {
            $validRedirectUri = $this->selectValidRedirectUri($client, $redirectFromQuery);
        }

        return [$client, $validRedirectUri];
    }

    /**
     * @param TokenInterface $token
     * @param int            $tokenExpiresIn
     *
     * @return ResponseInterface
     */
    protected function createBodyTokenResponse(TokenInterface $token, int $tokenExpiresIn): ResponseInterface
    {
        $scopeList  = empty($token->getScopeIdentifiers()) === true || $token->isScopeModified() === false ?
            null : implode(' ', $token->getScopeIdentifiers());

        // for access token format @link https://tools.ietf.org/html/rfc6749#section-5.1
        $parameters = $this->filterNulls([
            'access_token'  => $token->getValue(),
            'token_type'    => $token->getType(),
            'expires_in'    => $tokenExpiresIn,
            'refresh_token' => $token->getRefreshValue(),
            'scope'         => $scopeList,
        ]);

        $response = new JsonResponse($parameters, 200, [
            'Cache-Control' => 'no-store',
            'Pragma'        => 'no-cache'
        ]);

        return $response;
    }

    /**
     * @param TokenInterface $code
     * @param string|null    $state
     *
     * @return ResponseInterface
     */
    protected function createRedirectCodeResponse(TokenInterface $code, string $state = null): ResponseInterface
    {
        // TODO have to check that isScopeModified flag was saved with the code and if it is used (and have to)

        // for access token format @link https://tools.ietf.org/html/rfc6749#section-4.1.3
        $parameters = $this->filterNulls([
            'code'  => $code->getCode(),
            'state' => $state,
        ]);

        $redirectUri = $code->getRedirectUriString();
        $query       = $this->encodeAsXWwwFormUrlencoded($parameters);

        $response = new RedirectResponse((new Uri($redirectUri))->withQuery($query));

        return $response;
    }

    /**
     * @param TokenInterface $token
     * @param int            $tokenExpiresIn
     * @param string|null    $state
     *
     * @return ResponseInterface
     */
    protected function createRedirectTokenResponse(
        TokenInterface $token,
        int $tokenExpiresIn,
        string $state = null
    ): ResponseInterface {
        $scopeList  = $token->getScopeIdentifiers() === null || $token->isScopeModified() === false ?
            null : implode(' ', $token->getScopeIdentifiers());

        // for access token format @link https://tools.ietf.org/html/rfc6749#section-5.1
        $parameters = $this->filterNulls([
            'access_token' => $token->getValue(),
            'token_type'   => $token->getType(),
            'expires_in'   => $tokenExpiresIn,
            'scope'        => $scopeList,
            'state'        => $state,
        ]);

        $fragment = $this->encodeAsXWwwFormUrlencoded($parameters);

        $response = new RedirectResponse((new Uri($token->getRedirectUriString()))->withFragment($fragment));

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
     * @param TokenInterface $token
     *
     * @return int
     */
    protected function setUpTokenValue(TokenInterface $token): int
    {
        /** @var Token $token */
        assert($token instanceof Token);

        list($tokenValue, $tokenType, $tokenExpiresIn) =
            $this->getIntegration()->generateTokenValues($token);
        $token->setValue($tokenValue)->setType($tokenType);

        return $tokenExpiresIn;
    }

    /**
     * @param TokenInterface $token
     *
     * @return int
     */
    protected function setUpTokenValues(TokenInterface $token): int
    {
        /** @var Token $token */
        assert($token instanceof Token);

        list($tokenValue, $tokenType, $tokenExpiresIn, $refreshValue) =
            $this->getIntegration()->generateTokenValues($token);
        $token->setValue($tokenValue)->setType($tokenType)->setRefreshValue($refreshValue);

        return $tokenExpiresIn;
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
