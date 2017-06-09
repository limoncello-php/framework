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
use Limoncello\OAuthServer\Exceptions\OAuthCodeRedirectException;
use Limoncello\OAuthServer\Exceptions\OAuthRedirectException;
use Limoncello\OAuthServer\Exceptions\OAuthTokenBodyException;
use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Limoncello\Passport\Contracts\PassportServerIntegrationInterface;
use Limoncello\Passport\Contracts\PassportServerInterface;
use Limoncello\Passport\Entities\Token;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface as LAI;
use Psr\Log\LoggerAwareTrait;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Uri;

/**
 * @package Limoncello\Passport
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class BasePassportServer extends BaseAuthorizationServer implements PassportServerInterface, LAI
{
    use LoggerAwareTrait;

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
            list($client, $redirectUri) = $this->getValidClientAndRedirectUri(
                $this->codeGetClientId($parameters),
                $this->codeGetRedirectUri($parameters)
            );
            if ($client === null || $redirectUri === null) {
                return $this->getIntegration()->createInvalidClientAndRedirectUriErrorResponse();
            }

            $maxStateLength = $this->getMaxStateLength();
            switch ($responseType = $this->getResponseType($parameters)) {
                case ResponseTypes::AUTHORIZATION_CODE:
                    $this->logDebug('Handling code authorization.');
                    $response = $this
                        ->codeAskResourceOwnerForApproval($parameters, $client, $redirectUri, $maxStateLength);
                    break;
                case ResponseTypes::IMPLICIT:
                    $this->logDebug('Handling implicit authorization.');
                    $response = $this
                        ->implicitAskResourceOwnerForApproval($parameters, $client, $redirectUri, $maxStateLength);
                    break;
                default:
                    // @link https://tools.ietf.org/html/rfc6749#section-3.1.1 ->
                    // @link https://tools.ietf.org/html/rfc6749#section-4.1.2.1
                    $this->logDebug('Unsupported response type in request.', ['response_type' => $responseType]);
                    $errorCode = OAuthCodeRedirectException::ERROR_UNSUPPORTED_RESPONSE_TYPE;
                    throw new OAuthCodeRedirectException($errorCode, $redirectUri);
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

            switch ($grantType = $this->getGrantType($parameters)) {
                case GrantTypes::AUTHORIZATION_CODE:
                    $this->logDebug('Handling code grant.');
                    $response = $this->codeIssueToken($parameters, $determinedClient);
                    break;
                case GrantTypes::RESOURCE_OWNER_PASSWORD_CREDENTIALS:
                    $this->logDebug('Handling resource owner password grant.');
                    $response = $this->passIssueToken($parameters, $determinedClient);
                    break;
                case GrantTypes::CLIENT_CREDENTIALS:
                    $this->logDebug('Handling client credentials grant.');
                    if ($determinedClient === null) {
                        $this->logDebug('Client identification failed.');
                        throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_CLIENT);
                    }
                    $response = $this->clientIssueToken($parameters, $determinedClient);
                    break;
                case GrantTypes::REFRESH_TOKEN:
                    $this->logDebug('Handling refresh token grant.');
                    if ($determinedClient === null) {
                        $this->logDebug('Client identification failed.');
                        throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_CLIENT);
                    }
                    $response = $this->refreshIssueToken($parameters, $determinedClient);
                    break;
                default:
                    $this->logDebug('Unknown grant type.', ['grant_type' => $grantType]);
                    throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_UNSUPPORTED_GRANT_TYPE);
            }
        } catch (OAuthTokenBodyException $exception) {
            $response = $this->createBodyErrorResponse($exception);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function createCodeResponse(TokenInterface $code, string $state = null): ResponseInterface
    {
        $client = $this->getIntegration()->getClientRepository()->read($code->getClientIdentifier());
        if ($code->getRedirectUriString() === null ||
            in_array($code->getRedirectUriString(), $client->getRedirectUriStrings()) === false
        ) {
            $this->logDebug(
                'Code has invalid redirect URI which do not match any redirect URI for its client.',
                ['id' => $code->getIdentifier()]
            );
            return $this->getIntegration()->createInvalidClientAndRedirectUriErrorResponse();
        }

        $code->setCode($this->getIntegration()->generateCodeValue($code));

        $tokenRepo   = $this->getIntegration()->getTokenRepository();
        $createdCode = $tokenRepo->createCode($code);

        $response = $this->createRedirectCodeResponse($createdCode, $state);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function createTokenResponse(TokenInterface $token, string $state = null): ResponseInterface
    {
        $client = $this->getIntegration()->getClientRepository()->read($token->getClientIdentifier());
        if ($token->getRedirectUriString() === null ||
            in_array($token->getRedirectUriString(), $client->getRedirectUriStrings()) === false
        ) {
            $this->logDebug(
                'Token has invalid redirect URI which do not match any redirect URI for its client.',
                ['id' => $token->getIdentifier()]
            );
            return $this->getIntegration()->createInvalidClientAndRedirectUriErrorResponse();
        }

        list($tokenValue, $tokenType, $tokenExpiresIn) = $this->getIntegration()->generateTokenValues($token);

        // refresh value must be null by the spec
        $refreshValue = null;
        $token->setValue($tokenValue)->setType($tokenType)->setRefreshValue($refreshValue);
        $savedToken = $this->getIntegration()->getTokenRepository()->createToken($token);

        $response = $this->createRedirectTokenResponse($savedToken, $tokenExpiresIn, $state);

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
        $this->logDebug('Asking resource owner for scope approval (code grant).');
        return $this->getIntegration()->createAskResourceOwnerForApprovalResponse(
            ResponseTypes::AUTHORIZATION_CODE,
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
        assert($code instanceof TokenInterface);

        /** @var TokenInterface $code */

        $identifier = $code->getIdentifier();
        $this->logDebug('Revoking token.', ['token_id' => $identifier]);
        $this->getIntegration()->getTokenRepository()->disable($identifier);
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
        $this->logDebug('Asking resource owner for scope approval (implicit grant).');
        return $this->getIntegration()->createAskResourceOwnerForApprovalResponse(
            ResponseTypes::IMPLICIT,
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
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
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
            $this->logDebug('User not found with provided username and password.', ['username' => $userName]);
            throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_GRANT);
        }
        assert(is_int($userIdentifier) === true);
        $this->logDebug('User authenticated with provided username and password.', ['username' => $userName]);

        $changedScopeOrNull = $this->getIntegration()->verifyAllowedUserScope($userIdentifier, $scope);
        if ($changedScopeOrNull !== null) {
            assert(is_array($changedScopeOrNull));
            $isScopeModified = true;
            $scope           = $changedScopeOrNull;
        }

        $unsavedToken = $this->getIntegration()->createTokenInstance();
        $unsavedToken
            ->setClientIdentifier($client->getIdentifier())
            ->setScopeIdentifiers($scope)
            ->setUserIdentifier($userIdentifier);
        $isScopeModified === true ? $unsavedToken->setScopeModified() : $unsavedToken->setScopeUnmodified();


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
        $this->logDebug('Prepare token for client.');
        assert($client !== null);

        $unsavedToken = $this->getIntegration()->createTokenInstance();
        $unsavedToken
            ->setClientIdentifier($client->getIdentifier())
            ->setScopeIdentifiers($scope);
        $isScopeModified === true ? $unsavedToken->setScopeModified() : $unsavedToken->setScopeUnmodified();


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
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function refreshCreateAccessTokenResponse(
        ClientInterface $client,
        \Limoncello\OAuthServer\Contracts\TokenInterface $token,
        bool $isScopeModified,
        array $scope = null,
        array $extraParameters = []
    ): ResponseInterface {
        $this->logDebug('Prepare refresh token.');

        /** @var TokenInterface $token */
        assert($token instanceof TokenInterface);

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
            $updatedToken->setScopeModified()->setScopeIdentifiers($scope);
        }
        $response = $this->createBodyTokenResponse($updatedToken, $tokenExpiresIn);

        return $response;
    }

    /**
     * @param string|null $clientId
     * @param string|null $redirectFromQuery
     *
     * @return array [client|null, uri|null]
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function getValidClientAndRedirectUri(string $clientId = null, string $redirectFromQuery = null)
    {
        $client           = null;
        $validRedirectUri = null;

        if ($clientId !== null &&
            ($client = $this->getIntegration()->getClientRepository()->read($clientId)) !== null
        ) {
            $validRedirectUri = $this->selectValidRedirectUri($client, $redirectFromQuery);
            if ($validRedirectUri === null) {
                $this->logDebug(
                    'Choosing valid redirect URI for client failed.',
                    ['client_id' => $clientId, 'redirect_uri_from_query' => $redirectFromQuery]
                );
            }
        } else {
            $this->logDebug('Client is not found.', ['client_id' => $clientId]);
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
        $this->logDebug('Sending token as JSON response.');

        $scopeList  = $token->isScopeModified() === false || empty($token->getScopeIdentifiers()) === true ?
            null : $token->getScopeList();

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
        $this->logDebug('Sending code as redirect response.');

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
        $this->logDebug('Sending token as redirect response.');

        $scopeList  = $token->isScopeModified() === false || empty($token->getScopeIdentifiers()) === true ?
            null : $token->getScopeList();

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
        $data = $this->filterNulls([
            'error'             => $exception->getErrorCode(),
            'error_description' => $exception->getErrorDescription(),
            'error_uri'         => $this->getBodyErrorUri($exception),
        ]);

        $this->logDebug('Sending OAuth error as JSON response.', $data);

        $response = new JsonResponse($data, $exception->getHttpCode(), $exception->getHttpHeaders());

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

        $this->logDebug('Sending OAuth error via redirect.', $parameters);

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
     * @return self
     */
    protected function setIntegration(PassportServerIntegrationInterface $integration): self
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
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    protected function logDebug(string $message, array $context = [])
    {
        if ($this->logger !== null) {
            $this->logger->debug($message, $context);
        }
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
