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
use Limoncello\OAuthServer\Contracts\Integration\ImplicitIntegrationInterface;
use Limoncello\OAuthServer\Exceptions\OAuthTokenRedirectException;
use Psr\Http\Message\ResponseInterface;

/**
 * Implements Implicit Grant.
 *
 * @package Limoncello\OAuthServer
 *
 * @link https://tools.ietf.org/html/rfc6749#section-1.3
 * @link https://tools.ietf.org/html/rfc6749#section-4.2
 */
trait ImplicitGrantTrait
{
    /**
     * @var ImplicitIntegrationInterface
     */
    private $implicitIntegration;

    /**
     * @param ImplicitIntegrationInterface $integration
     *
     * @return void
     */
    public function implicitSetIntegration(ImplicitIntegrationInterface $integration)
    {
        $this->implicitIntegration = $integration;
    }

    /**
     * @return ImplicitIntegrationInterface
     */
    protected function implicitGetIntegration()
    {
        return $this->implicitIntegration;
    }

    /**
     * @param string[] $parameters
     *
     * @return string|null
     */
    protected function implicitGetClientId(array $parameters)
    {
        return array_key_exists('client_id', $parameters) === true ?
            $parameters['client_id'] : null;
    }

    /**
     * @param string[] $parameters
     *
     * @return string|null
     */
    protected function implicitGetRedirectUri(array $parameters)
    {
        return array_key_exists('redirect_uri', $parameters) === true ?
            $parameters['redirect_uri'] : null;
    }

    /**
     * @param string[] $parameters
     *
     * @return string[]|null
     */
    protected function implicitGetScope(array $parameters)
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
    protected function implicitGetState(array $parameters)
    {
        return array_key_exists('state', $parameters) === true ?
            $parameters['state'] : null;
    }

    /**
     * @param string[]        $parameters
     * @param ClientInterface $client
     * @param string|null     $redirectUri
     * @param int|null        $maxStateLength
     *
     * @return ResponseInterface
     */
    protected function implicitAskResourceOwnerForApproval(
        array $parameters,
        ClientInterface $client,
        string $redirectUri = null,
        int $maxStateLength = null
    ): ResponseInterface {
        $state = $this->implicitGetState($parameters);
        if ($maxStateLength !== null && strlen($state) > $maxStateLength) {
            // TODO Think of using helper factory for exceptions in order to reduce param list / use all params
            throw new OAuthTokenRedirectException(
                OAuthTokenRedirectException::ERROR_INVALID_REQUEST,
                $redirectUri,
                $state
            );
        }

        if ($client->isImplicitGrantEnabled() === false) {
            // TODO Think of using helper factory for exceptions in order to reduce param list / use all params
            throw new OAuthTokenRedirectException(
                OAuthTokenRedirectException::ERROR_UNAUTHORIZED_CLIENT,
                $redirectUri,
                $state
            );
        }

        $scope = $this->implicitGetScope($parameters);
        list ($isScopeValid, $scopeList, $isScopeModified) =
            $this->implicitGetIntegration()->implicitValidateScope($client, $scope);
        if ($isScopeValid === false) {
            // TODO Think of using helper factory for exceptions in order to reduce param list / use all params
            throw new OAuthTokenRedirectException(
                OAuthTokenRedirectException::ERROR_INVALID_SCOPE,
                $redirectUri,
                $state
            );
        }

        $response = $this->implicitGetIntegration()->implicitCreateAskResourceOwnerForApprovalResponse(
            $client,
            $redirectUri,
            $isScopeModified,
            $scopeList,
            $state
        );

        return $response;
    }
}
