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

import { ErrorLinksInterface } from './ErrorLinksInterface';
import { QueryParameterInterface } from './QueryParameterInterface';
import { DocumentPointerInterface } from './DocumentPointerInterface';

/**
 * Error Object.
 *
 * @link http://jsonapi.org/format/#error-objects
 */
export interface ErrorObjectInterface {
    /**
     * Unique identifier for this particular occurrence of the problem.
     */
    readonly id?: string;
    /**
     * Error links object.
     */
    readonly links?: ErrorLinksInterface;
    /**
     * Application-specific error code.
     */
    readonly code?: string;
    /**
     * A short, human-readable summary of the problem that
     * SHOULD NOT change from occurrence to occurrence of the problem,
     * except for purposes of localization.
     */
    readonly title?: string;
    /**
     * Human-readable explanation specific to this occurrence of the problem.
     * This value can be localized.
     */
    readonly detail?: string;
    /**
     * Object containing references to the source of the error.
     */
    readonly source?: DocumentPointerInterface | QueryParameterInterface;
    /**
     * Non-standard meta-information about the error.
     */
    readonly meta: any;
}
