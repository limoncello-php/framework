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
 * OAuth 2.0 token description.
 *
 * @link https://tools.ietf.org/html/rfc6749#section-5.1
 */
export interface TokenInterface {
    /**
     * The access token issued by the authorization server.
     */
    readonly access_token: string;
    /**
     * The type of the token issued.
     */
    readonly token_type: string;
    /**
     * The lifetime in seconds of the access token.
     */
    readonly expires_in?: number;
    /**
     * The refresh token, which can be used to obtain new access tokens using the same authorization grant.
     */
    readonly refresh_token?: string;
    /**
     * The scope of the access token.
     */
    readonly scope?: string;
}
