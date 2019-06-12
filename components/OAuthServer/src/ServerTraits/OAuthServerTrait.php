<?php declare(strict_types=1);

namespace Limoncello\OAuthServer\ServerTraits;

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

use Limoncello\OAuthServer\Contracts\ClientInterface;
use function array_diff;
use function array_key_exists;
use function count;
use function in_array;

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
    public function setInputUriOptional(): void
    {
        $this->isInputUriOptional = true;
    }

    /**
     * @return void
     */
    public function setInputUriMandatory(): void
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
    protected function getResponseType(array $parameters): ?string
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
    protected function getGrantType(array $parameters): ?string
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
     * @return string|null
     */
    protected function selectValidRedirectUri(ClientInterface $client, string $redirectUri = null): ?string
    {
        $validUri = null;
        $uris     = $client->getRedirectUriStrings();
        if (empty($redirectUri) === true) {
            // if no redirect provided and it's optional we require client to have
            // exactly 1 redirect URI so we know where to redirect.
            if (($this->isInputUriOptional() === true && count($uris) === 1)) {
                $validUri = $uris[0];
            }

            return $validUri;
        }

        // check client has provided redirect URI
        if (in_array($redirectUri, $client->getRedirectUriStrings()) === true) {
            $validUri = $redirectUri;
        }

        return $validUri;
    }
}
