<?php namespace Limoncello\OAuthServer\ServerTraits;

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

use Limoncello\OAuthServer\Contracts\ClientInterface;

/**
 * @package Limoncello\OAuthServer
 */
trait OAuthServerTrait
{
    /**
     * If input redirect URI is optional (client default URI will be used if possible).
     *
     * @var bool
     */
    private $isInputUriOptional = true;

    /**
     * @return boolean
     */
    public function isInputUriOptional(): bool
    {
        return $this->isInputUriOptional;
    }

    /**
     * @return void
     */
    public function setInputUriOptional()
    {
        $this->isInputUriOptional = true;
    }

    /**
     * @return void
     */
    public function setInputUriMandatory()
    {
        $this->isInputUriOptional = false;
    }

    /**
     * @param string[] $parameters
     *
     * @return string|null
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.1
     * @link https://tools.ietf.org/html/rfc6749#section-4.2.1
     */
    protected function getResponseType(array $parameters)
    {
        return array_key_exists('response_type', $parameters) === true ? $parameters['response_type'] : null;
    }

    /**
     * @param string[] $parameters
     *
     * @return string|null
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.3
     * @link https://tools.ietf.org/html/rfc6749#section-4.3.2
     * @link https://tools.ietf.org/html/rfc6749#section-4.4.2
     */
    protected function getGrantType(array $parameters)
    {
        return array_key_exists('grant_type', $parameters) === true ? $parameters['grant_type'] : null;
    }

    /**
     * @param array|null           $scopes
     * @param ClientInterface|null $client
     *
     * @return array [bool $isScopeValid, string[]|null $scopeList, bool $isScopeModified]
     */
    protected function validateScope(ClientInterface $client, array $scopes = null): array
    {
        if (empty($scopes) === true) {
            $clientScopes = $client->getScopeIdentifiers();
            $isModified   = $clientScopes !== $scopes;

            return $client->isUseDefaultScopesOnEmptyRequest() === true ?
                [true, $clientScopes, $isModified] : [false, $scopes, false];
        }

        $extraScopes    = array_diff($scopes, $client->getScopeIdentifiers());
        $hasExtraScopes = count($extraScopes) > 0;
        $isInvalidScope = $hasExtraScopes === true && $client->isScopeExcessAllowed() === false;

        return $isInvalidScope === true ? [false, $scopes, false] : [true, $scopes, false];
    }

    /**
     * @param ClientInterface $client
     * @param string|null     $redirectUri
     *
     * @return bool
     */
    protected function isValidRedirectUri(ClientInterface $client, string $redirectUri = null)
    {
        $uris = $client->getRedirectUriStrings();
        if (empty($redirectUri) === true) {
            // if no redirect provided and it's optional we require client to have
            // exactly 1 redirect URI so we know where to redirect.
            return ($this->isInputUriOptional() === true && count($uris) === 1);
        }

        // check client has provided redirect URI
        $isValid = false;
        foreach ($client->getRedirectUriStrings() as $uri) {
            if ($uri === $redirectUri) {
                $isValid = true;
                break;
            }
        }

        return $isValid;
    }

    /**
     * @param ClientInterface $client
     * @param string|null     $receivedRedirectUri
     *
     * @return string
     */
    protected function selectRedirectUri(ClientInterface $client, string $receivedRedirectUri = null): string
    {
        if ($receivedRedirectUri !== null) {
            assert(
                call_user_func(function () use ($client, $receivedRedirectUri) {
                    foreach ($client->getRedirectUriStrings() as $uri) {
                        if ($uri === $receivedRedirectUri) {
                            return true;
                        }
                    }

                    return false;
                }) === true,
                'Authentication server logic should not allow processing received redirect URI which does not belong ' .
                'to client.'
            );

            return $receivedRedirectUri;
        }

        $redirectionUris = $client->getRedirectUriStrings();

        assert(
            count($redirectionUris) === 1,
            'Authentication server logic should not allow processing clients with more than one redirect URI ' .
            'if no redirect URI from client received.'
        );

        $result = $redirectionUris[0];

        return $result;
    }
}
