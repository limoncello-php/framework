<?php namespace Limoncello\OAuthServer\Exceptions;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use Exception;

/**
 * @package Limoncello\OAuthServer
 */
class OAuthTokenBodyException extends OAuthServerException
{
    /**
     * Error code.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-5.2
     */
    const ERROR_INVALID_REQUEST = 'invalid_request';

    /**
     * Error code.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-5.2
     */
    const ERROR_INVALID_CLIENT = 'invalid_client';

    /**
     * Error code.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-5.2
     */
    const ERROR_INVALID_GRANT = 'invalid_grant';

    /**
     * Error code.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-5.2
     */
    const ERROR_UNAUTHORIZED_CLIENT = 'unauthorized_client';

    /**
     * Error code.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-5.2
     */
    const ERROR_UNSUPPORTED_GRANT_TYPE = 'unsupported_grant_type';

    /**
     * Error code.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-5.2
     */
    const ERROR_INVALID_SCOPE = 'invalid_scope';

    /**
     * Default error messages.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-5.2
     */
    const DEFAULT_MESSAGES = [
        self::ERROR_INVALID_REQUEST => 'The request is missing a required parameter, includes an unsupported ' .
            'parameter value (other than grant type), repeats a parameter, includes multiple credentials, utilizes ' .
            'more than one mechanism for authenticating the client, or is otherwise malformed.',

        self::ERROR_INVALID_CLIENT => 'Client authentication failed (e.g., unknown client, no client ' .
            'authentication included, or unsupported authentication method).',

        self::ERROR_INVALID_GRANT => 'The provided authorization grant (e.g., authorization code, resource owner ' .
            'credentials) or refresh token is invalid, expired, revoked, does not match the redirection URI used in ' .
            'the authorization request, or was issued to another client.',

        self::ERROR_UNAUTHORIZED_CLIENT => 'The authenticated client is not authorized to use this ' .
            'authorization grant type.',

        self::ERROR_UNSUPPORTED_GRANT_TYPE => 'The authorization grant type is not supported by the ' .
            'authorization server.',

        self::ERROR_INVALID_SCOPE => 'The requested scope is invalid, unknown, malformed, or exceeds the scope ' .
            'granted by the resource owner.',
    ];

    /**
     * @var string
     */
    private $errorCode;

    /**
     * @var int
     */
    private $httpCode;

    /**
     * @var string[]
     */
    private $httpHeaders;

    /**
     * @var string|null
     */
    private $errorUri;

    /**
     * @param string         $errorCode
     * @param string|null    $errorUri
     * @param int            $httpCode
     * @param string[]       $httpHeaders
     * @param string[]|null  $descriptions
     * @param Exception|null $previous
     */
    public function __construct(
        string $errorCode,
        string $errorUri = null,
        int $httpCode = 400,
        array $httpHeaders = [],
        array $descriptions = null,
        Exception $previous = null
    ) {
        $descriptions = $descriptions === null ? self::DEFAULT_MESSAGES : $descriptions;

        parent::__construct($descriptions[$errorCode], 0, $previous);

        // @link https://tools.ietf.org/html/rfc6749#section-5.2
        //
        // The authorization server includes the HTTP "Cache-Control" response header field with a value of "no-store"
        // in response as well as the "Pragma" response header field with a value of "no-cache".
        $cacheHeaders = [
            'Cache-Control' => 'no-store',
            'Pragma'        => 'no-cache'
        ];

        $this->errorCode   = $errorCode;
        $this->errorUri    = $errorUri;
        $this->httpCode    = $httpCode;
        $this->httpHeaders = $httpHeaders + $cacheHeaders;
    }

    /**
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getErrorDescription(): string
    {
        return $this->getMessage();
    }

    /**
     * @return string|null
     */
    public function getErrorUri(): ?string
    {
        return $this->errorUri;
    }

    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * @return string[]
     */
    public function getHttpHeaders(): array
    {
        return $this->httpHeaders;
    }
}
