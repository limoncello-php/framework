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

import { TokenInterface } from './TokenInterface';

/**
 * OAuth 2.0 authorization.
 *
 * @link https://tools.ietf.org/html/rfc6749#section-4
 */
export interface AuthorizerInterface {
    /**
     * Resource Owner Password Credentials Grant.
     *
     * If redirectUri is given it will be included into request to OAuth server.
     * If clientId is given it will be included into *not authenticated* request to server.
     * If clientId is not given an *authenticated* (with client auth info) request will be sent to server.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.3
     */
    code(authCode: string, redirectUri?: string, clientId?: string): Promise<TokenInterface>;

    /**
     * Resource Owner Password Credentials Grant.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.3
     */
    password(userName: string, password: string, scope?: string): Promise<TokenInterface>;

    /**
     * Client Credentials Grant.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.4
     */
    client(scope?: string): Promise<TokenInterface>;

    /**
     * Refreshing an Access Token.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-6
     */
    refresh(refreshToken: string, scope?: string): Promise<TokenInterface>;
}
