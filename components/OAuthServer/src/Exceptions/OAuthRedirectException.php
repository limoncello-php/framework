<?php namespace Limoncello\OAuthServer\Exceptions;

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

use Exception;

/**
 * @package Limoncello\OAuthServer
 */
abstract class OAuthRedirectException extends OAuthServerException
{
    /**
     * Error code.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.2.1
     * @link https://tools.ietf.org/html/rfc6749#section-4.2.2.1
     */
    const ERROR_INVALID_REQUEST = 'invalid_request';

    /**
     * Error code.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.2.1
     * @link https://tools.ietf.org/html/rfc6749#section-4.2.2.1
     */
    const ERROR_UNAUTHORIZED_CLIENT = 'unauthorized_client';

    /**
     * Error code.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.2.1
     * @link https://tools.ietf.org/html/rfc6749#section-4.2.2.1
     */
    const ERROR_ACCESS_DENIED = 'access_denied';

    /**
     * Error code.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.2.1
     * @link https://tools.ietf.org/html/rfc6749#section-4.2.2.1
     */
    const ERROR_UNSUPPORTED_RESPONSE_TYPE = 'unsupported_response_type';

    /**
     * Error code.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.2.1
     * @link https://tools.ietf.org/html/rfc6749#section-4.2.2.1
     */
    const ERROR_INVALID_SCOPE = 'invalid_scope';

    /**
     * Error code.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.2.1
     * @link https://tools.ietf.org/html/rfc6749#section-4.2.2.1
     */
    const ERROR_SERVER_ERROR = 'server_error';

    /**
     * Error code.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.2.1
     * @link https://tools.ietf.org/html/rfc6749#section-4.2.2.1
     */
    const ERROR_TEMPORARILY_UNAVAILABLE = 'temporarily_unavailable';

    /**
     * Default error messages. The actual messages should be defined in child classes.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.2.1
     * @link https://tools.ietf.org/html/rfc6749#section-4.2.2.1
     */
    const DEFAULT_MESSAGES = null;

    /**
     * @var string
     */
    private $errorCode;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var string|null
     */
    private $errorUri;

    /**
     * @var null|string
     */
    private $state;

    /**
     * @var string[]
     */
    private $httpHeaders;

    /**
     * @param string         $errorCode
     * @param string         $redirectUri
     * @param string|null    $state
     * @param string|null    $errorUri
     * @param string[]       $httpHeaders
     * @param string[]|null  $descriptions
     * @param Exception|null $previous
     */
    public function __construct(
        string $errorCode,
        string $redirectUri,
        string $state = null,
        string $errorUri = null,
        array $httpHeaders = [],
        array $descriptions = null,
        Exception $previous = null
    ) {
        $descriptions = $descriptions === null ? static::DEFAULT_MESSAGES : $descriptions;

        parent::__construct($descriptions[$errorCode], 0, $previous);

        $this->errorCode   = $errorCode;
        $this->redirectUri = $redirectUri;
        $this->state       = $state;
        $this->errorUri    = $errorUri;
        $this->httpHeaders = $httpHeaders;
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
     * @return string
     */
    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    /**
     * @return string|null
     */
    public function getErrorUri()
    {
        return $this->errorUri;
    }

    /**
     * @return null|string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return string[]
     */
    public function getHttpHeaders(): array
    {
        return $this->httpHeaders;
    }
}
