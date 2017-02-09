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

/**
 * @package Limoncello\OAuthServer
 */
class OAuthCodeRedirectException extends OAuthRedirectException
{
    /**
     * Default error messages.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.2.1
     */
    const DEFAULT_MESSAGES = [
        self::ERROR_INVALID_REQUEST =>
            'The request is missing a required parameter, includes an invalid parameter value, ' .
            'includes a parameter more than once, or is otherwise malformed.',

        self::ERROR_UNAUTHORIZED_CLIENT =>
            'The client is not authorized to request an authorization code using this method.',

        self::ERROR_ACCESS_DENIED =>
            'The resource owner or authorization server denied the request.',

        self::ERROR_UNSUPPORTED_RESPONSE_TYPE =>
            'The authorization server does not support obtaining an authorization code using this method.',

        self::ERROR_INVALID_SCOPE =>
            'The requested scope is invalid, unknown, or malformed.',

        self::ERROR_SERVER_ERROR =>
            'The authorization server encountered an unexpected condition ' .
            'that prevented it from fulfilling the request.',

        self::ERROR_TEMPORARILY_UNAVAILABLE =>
            'The authorization server is currently unable to handle the request due to ' .
            'a temporary overloading or maintenance of the server.',
    ];
}
