<?php namespace Limoncello\OAuthServer\GrantTraits;

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
use Limoncello\OAuthServer\Contracts\Clients\ClientInterface;
use Limoncello\OAuthServer\Contracts\Integration\CodeIntegrationInterface;
use Limoncello\OAuthServer\Exceptions\OAuthCodeRedirectException;
use Limoncello\OAuthServer\Exceptions\OAuthTokenBodyException;
use Psr\Http\Message\ResponseInterface;

/**
 * @package Limoncello\OAuthServer
 *
 * @link https://tools.ietf.org/html/rfc6749#section-1.3
 */
trait CodeGrantTrait
{
    /**
     * @var CodeIntegrationInterface
     */
    private $codeIntegration;

    /**
     * @param CodeIntegrationInterface $integration
     *
     * @return void
     */
    public function codeSetIntegration(CodeIntegrationInterface $integration)
    {
        $this->codeIntegration = $integration;
    }

    /**
     * @return CodeIntegrationInterface
     */
    protected function codeGetIntegration()
    {
        return $this->codeIntegration;
    }

    /**
     * @param string[] $parameters
     *
     * @return string|null
     */
    protected function codeGetClientId(array $parameters)
    {
        return array_key_exists('client_id', $parameters) === true ?
            $parameters['client_id'] : null;
    }

    /**
     * @param string[] $parameters
     *
     * @return string|null
     */
    protected function codeGetRedirectUri(array $parameters)
    {
        return array_key_exists('redirect_uri', $parameters) === true ?
            $parameters['redirect_uri'] : null;
    }

    /**
     * @param string[] $parameters
     *
     * @return string[]|null
     */
    protected function codeGetScope(array $parameters)
    {
        $scope    = null;
        $hasScope =
            array_key_exists('scope', $parameters) === true &&
            is_string($scope = $parameters['scope']) === true;

        return $hasScope === true ? explode(' ', $scope) : null;
    }

    /**
     * @param string[] $parameters
     *
     * @return string|null
     */
    protected function codeGetState(array $parameters)
    {
        return array_key_exists('state', $parameters) === true ?
            $parameters['state'] : null;
    }

    /**
     * @param string[] $parameters
     *
     * @return string|null
     */
    protected function codeGetCode(array $parameters)
    {
        return array_key_exists('code', $parameters) === true ?
            $parameters['code'] : null;
    }

    /**
     * @param string[]        $parameters
     * @param ClientInterface $client
     * @param string|null     $redirectUri
     * @param int|null        $maxStateLength
     *
     * @return ResponseInterface
     */
    protected function codeAskResourceOwnerForApproval(
        array $parameters,
        ClientInterface $client,
        string $redirectUri = null,
        int $maxStateLength = null
    ): ResponseInterface {
        // TODO invalid redirect uri for exceptions (can be null) should take from client if null
        $state = $this->codeGetState($parameters);
        if ($maxStateLength !== null && strlen($state) > $maxStateLength) {
            // TODO Think of using helper factory for exceptions in order to reduce param list / use all params
            throw new OAuthCodeRedirectException(
                OAuthCodeRedirectException::ERROR_INVALID_REQUEST,
                $redirectUri,
                $state
            );
        }

        if ($client->isCodeGrantEnabled() === false) {
            // TODO Think of using helper factory for exceptions in order to reduce param list / use all params
            throw new OAuthCodeRedirectException(
                OAuthCodeRedirectException::ERROR_UNAUTHORIZED_CLIENT,
                $redirectUri,
                $state
            );
        }

        $scope = $this->codeGetScope($parameters);
        list ($isScopeValid, $scopeList, $isScopeModified) =
            $this->codeGetIntegration()->codeValidateScope($client, $scope);
        if ($isScopeValid === false) {
            // TODO Think of using helper factory for exceptions in order to reduce param list / use all params
            throw new OAuthCodeRedirectException(
                OAuthCodeRedirectException::ERROR_INVALID_SCOPE,
                $redirectUri,
                $state
            );
        }

        $response = $this->codeGetIntegration()->codeCreateAskResourceOwnerForApprovalResponse(
            $client,
            $redirectUri,
            $isScopeModified,
            $scopeList,
            $state
        );

        return $response;
    }

    /**
     * @param string[]             $parameters
     * @param ClientInterface|null $authenticatedClient
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function codeIssueToken(array $parameters, ClientInterface $authenticatedClient = null): ResponseInterface
    {
        $clientId = $this->codeGetClientId($parameters);
        if ($authenticatedClient === null) {
            // then client_id is required @link https://tools.ietf.org/html/rfc6749#section-4.1.3
            if ($clientId === null ||
                ($client = $this->codeGetIntegration()->codeReadClient($clientId)) === null ||
                $client->isCodeGrantEnabled() === false ||
                $client->isConfidential() === true
            ) {
                // TODO we limit here possible Exception params. Think of a) add b) use helper factory
                throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_UNAUTHORIZED_CLIENT);
            }
        } else {
            if ($clientId !== null && $clientId !== $authenticatedClient->getIdentifier()) {
                // TODO we limit here possible Exception params. Think of a) add b) use helper factory
                throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_UNAUTHORIZED_CLIENT);
            }
            $client = $authenticatedClient;
        }

        if (($codeValue = $this->codeGetCode($parameters)) === null ||
            ($code = $this->codeGetIntegration()->codeReadAuthenticationCode($codeValue)) === null
        ) {
            // TODO we limit here possible Exception params. Think of a) add b) use helper factory
            throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_GRANT);
        }

        if ($code->hasBeenUsedEarlier() === true) {
            $this->codeGetIntegration()->codeRevokeTokens($code);
            // TODO we limit here possible Exception params. Think of a) add b) use helper factory
            throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_GRANT);
        }

        if ($code->getClientIdentifier() !== $client->getIdentifier()) {
            // TODO we limit here possible Exception params. Think of a) add b) use helper factory
            throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_UNAUTHORIZED_CLIENT);
        }

        if ($code->getRedirectUri() !== null) {
            // REQUIRED, if the "redirect_uri" parameter was included in the authorization request
            if (($redirectUri = $this->codeGetRedirectUri($parameters)) === null ||
                $redirectUri !== $code->getRedirectUri()
            ) {
                // TODO we limit here possible Exception params. Think of a) add b) use helper factory
                throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_GRANT);
            }
        }

        $response = $this->codeGetIntegration()->codeCreateAccessTokenResponse($code);

        return $response;
    }
}
