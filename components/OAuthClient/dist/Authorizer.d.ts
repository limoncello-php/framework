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
import AuthorizerInterface from './Contracts/AuthorizerInterface';
import TokenInterface from './Contracts/TokenInterface';
import ClientRequestsInterface from './Contracts/ClientRequestsInterface';
/**
 * @inheritdoc
 */
export default class  implements AuthorizerInterface {
    /**
     * Constructor.
     */
    constructor(requests: ClientRequestsInterface);
    /**
     * @inheritdoc
     */
    code(authCode: string, redirectUri?: string | undefined, clientId?: string | undefined): Promise<TokenInterface>;
    /**
     * @inheritdoc
     */
    password(userName: string, password: string, scope?: string): Promise<TokenInterface>;
    /**
     * @inheritdoc
     */
    client(scope?: string): Promise<TokenInterface>;
    /**
     * @inheritdoc
     */
    refresh(refreshToken: string, scope?: string): Promise<TokenInterface>;
    /**
     * Common code for parsing token responses.
     *
     * @param responsePromise
     */
    protected parseTokenResponse(responsePromise: Promise<Response>): Promise<TokenInterface>;
}
