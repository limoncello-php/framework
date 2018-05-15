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

import { ErrorResponseInterface } from './../Contracts/ErrorResponseInterface';

/**
 * OAuth token error.
 */
export class TokenError extends Error {
    /**
     * OAuth Error details.
     */
    public readonly reason: ErrorResponseInterface;

    /**
     * Constructor.
     */
    public constructor(reason: ErrorResponseInterface, ...args: any[]) {
        super(...args);

        this.reason = reason;
        if (reason.error_description !== null && reason.error_description !== undefined) {
            this.message = reason.error_description;
        }
    }
}
