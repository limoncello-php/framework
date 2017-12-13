"use strict";
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
Object.defineProperty(exports, "__esModule", { value: true });
var TokenError_1 = require("./TokenError");
/**
 * Error message.
 */
var RESPONSE_IS_NOT_JSON = 'Response is not in JSON format. It might be invalid token URL, network error, invalid response format or server-side error.';
/**
 * @inheritdoc
 */
var default_1 = /** @class */ (function () {
    /**
     * Constructor.
     */
    function default_1(requests) {
        this.requests = requests;
    }
    /**
     * @inheritdoc
     */
    default_1.prototype.code = function (authCode, redirectUri, clientId) {
        // NOTE: it implements steps 4.1.3 and 4.1.4 of the spec.
        // Steps 4.1.1 and 4.1.2 (getting authorization code) are outside of the implementation.
        // Technically those steps are plain redirect to authorization server (AS) and reading the code from URL in a
        // callback from AS. They are trivial and very specific to tools used for building the app.
        //
        // https://tools.ietf.org/html/rfc6749#section-4.1.3 and https://tools.ietf.org/html/rfc6749#section-4.1.4
        var addAuth = (clientId === undefined);
        var data = addAuth ?
            {
                grant_type: 'authorization_code',
                code: authCode
            } : {
            grant_type: 'authorization_code',
            code: authCode,
            client_id: clientId
        };
        if (redirectUri !== undefined) {
            data.redirect_uri = redirectUri;
        }
        return this.parseTokenResponse(this.requests.sendForm(data, addAuth));
    };
    /**
     * @inheritdoc
     */
    default_1.prototype.password = function (userName, password, scope) {
        var data = scope !== undefined ?
            {
                grant_type: 'password',
                username: userName,
                password: password,
                scope: scope,
            } : {
            grant_type: 'password',
            username: userName,
            password: password,
        };
        return this.parseTokenResponse(this.requests.sendForm(data, false));
    };
    /**
     * @inheritdoc
     */
    default_1.prototype.client = function (scope) {
        var data = scope !== undefined ?
            {
                grant_type: 'client_credentials',
                scope: scope
            } : {
            grant_type: 'client_credentials'
        };
        return this.parseTokenResponse(this.requests.sendForm(data, true));
    };
    /**
     * @inheritdoc
     */
    default_1.prototype.refresh = function (refreshToken, scope) {
        var data = scope !== undefined ?
            {
                grant_type: 'refresh_token',
                refresh_token: refreshToken,
                scope: scope,
            } : {
            grant_type: 'refresh_token',
            refresh_token: refreshToken,
        };
        return this.parseTokenResponse(this.requests.sendForm(data, false));
    };
    /**
     * Common code for parsing token responses.
     *
     * @param responsePromise
     */
    default_1.prototype.parseTokenResponse = function (responsePromise) {
        return responsePromise
            .then(function (response) { return Promise.all([
            response.json(),
            Promise.resolve(response.ok),
        ]); })
            .then(function (results) {
            var json = results[0], isOk = results[1];
            var token = json;
            return isOk === true && token.access_token !== undefined && token.token_type !== undefined ?
                Promise.resolve(token) :
                Promise.reject(new TokenError_1.default(json));
        })
            .catch(function (error) { return Promise.reject(error.reason !== undefined ? error : new TypeError(RESPONSE_IS_NOT_JSON)); });
    };
    return default_1;
}());
exports.default = default_1;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiQXV0aG9yaXplci5qcyIsInNvdXJjZVJvb3QiOiIvaG9tZS9uZW9tZXJ4L1Byb2plY3RzL2xpbW9uY2VsbG8vZnJhbWV3b3JrL2NvbXBvbmVudHMvT0F1dGhDbGllbnQvZGlzdC8iLCJzb3VyY2VzIjpbIkF1dGhvcml6ZXIudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IjtBQUFBOzs7Ozs7Ozs7Ozs7OztHQWNHOztBQUtILDJDQUFzQztBQUd0Qzs7R0FFRztBQUNILElBQU0sb0JBQW9CLEdBQ3RCLDZIQUE2SCxDQUFDO0FBRWxJOztHQUVHO0FBQ0g7SUFRSTs7T0FFRztJQUNILG1CQUFtQixRQUFpQztRQUNoRCxJQUFJLENBQUMsUUFBUSxHQUFHLFFBQVEsQ0FBQztJQUM3QixDQUFDO0lBRUQ7O09BRUc7SUFDSCx3QkFBSSxHQUFKLFVBQUssUUFBZ0IsRUFBRSxXQUFnQyxFQUFFLFFBQTZCO1FBQ2xGLHlEQUF5RDtRQUN6RCx3RkFBd0Y7UUFDeEYsNkdBQTZHO1FBQzdHLDJGQUEyRjtRQUMzRixFQUFFO1FBQ0YsMEdBQTBHO1FBRTFHLElBQU0sT0FBTyxHQUFZLENBQUMsUUFBUSxLQUFLLFNBQVMsQ0FBQyxDQUFDO1FBQ2xELElBQU0sSUFBSSxHQUFRLE9BQU8sQ0FBQyxDQUFDO1lBQ3ZCO2dCQUNJLFVBQVUsRUFBRSxvQkFBb0I7Z0JBQ2hDLElBQUksRUFBRSxRQUFRO2FBQ2pCLENBQUMsQ0FBQyxDQUFDO1lBQ0EsVUFBVSxFQUFFLG9CQUFvQjtZQUNoQyxJQUFJLEVBQUUsUUFBUTtZQUNkLFNBQVMsRUFBRSxRQUFRO1NBQ3RCLENBQUM7UUFDTixFQUFFLENBQUMsQ0FBQyxXQUFXLEtBQUssU0FBUyxDQUFDLENBQUMsQ0FBQztZQUM1QixJQUFJLENBQUMsWUFBWSxHQUFHLFdBQVcsQ0FBQztRQUNwQyxDQUFDO1FBRUQsTUFBTSxDQUFDLElBQUksQ0FBQyxrQkFBa0IsQ0FBQyxJQUFJLENBQUMsUUFBUSxDQUFDLFFBQVEsQ0FBQyxJQUFJLEVBQUUsT0FBTyxD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QUMsQUF2SEQsSUF1SEMiLCJzb3VyY2VzQ29udGVudCI6WyIvKipcbiAqIENvcHlyaWdodCAyMDE1LTIwMTcgaW5mb0BuZW9tZXJ4LmNvbVxuICpcbiAqIExpY2Vuc2VkIHVuZGVyIHRoZSBBcGFjaGUgTGljZW5zZSwgVmVyc2lvbiAyLjAgKHRoZSBcIkxpY2Vuc2VcIik7XG4gKiB5b3UgbWF5IG5vdCB1c2UgdGhpcyBmaWxlIGV4Y2VwdCBpbiBjb21wbGlhbmNlIHdpdGggdGhlIExpY2Vuc2UuXG4gKiBZb3UgbWF5IG9idGFpbiBhIGNvcHkgb2YgdGhlIExpY2Vuc2UgYXRcbiAqXG4gKiBodHRwOi8vd3d3LmFwYWNoZS5vcmcvbGljZW5zZXMvTElDRU5TRS0yLjBcbiAqXG4gKiBVbmxlc3MgcmVxdWlyZWQgYnkgYXBwbGljYWJsZSBsYXcgb3IgYWdyZWVkIHRvIGluIHdyaXRpbmcsIHNvZnR3YXJlXG4gKiBkaXN0cmlidXRlZCB1bmRlciB0aGUgTGljZW5zZSBpcyBkaXN0cmlidXRlZCBvbiBhbiBcIkFTIElTXCIgQkFTSVMsXG4gKiBXSVRIT1VUIFdBUlJBTlRJRVMgT1IgQ09ORElUSU9OUyBPRiBBTlkgS0lORCwgZWl0aGVyIGV4cHJlc3Mgb3IgaW1wbGllZC5cbiAqIFNlZSB0aGUgTGljZW5zZSBmb3IgdGhlIHNwZWNpZmljIGxhbmd1YWdlIGdvdmVybmluZyBwZXJtaXNzaW9ucyBhbmRcbiAqIGxpbWl0YXRpb25zIHVuZGVyIHRoZSBMaWNlbnNlLlxuICovXG5cbmltcG9ydCBBdXRob3JpemVySW50ZXJmYWNlIGZyb20gJy4vQ29udHJhY3RzL0F1dGhvcml6ZXJJbnRlcmZhY2UnO1xuaW1wb3J0IFRva2VuSW50ZXJmYWNlIGZyb20gJy4vQ29udHJhY3RzL1Rva2VuSW50ZXJmYWNlJztcbmltcG9ydCBDbGllbnRSZXF1ZXN0c0ludGVyZmFjZSBmcm9tICcuL0NvbnRyYWN0cy9DbGllbnRSZXF1ZXN0c0ludGVyZmFjZSc7XG5pbXBvcnQgVG9rZW5FcnJvciBmcm9tICcuL1Rva2VuRXJyb3InO1xuaW1wb3J0IEVycm9yUmVzcG9uc2VJbnRlcmZhY2UgZnJvbSAnLi9Db250cmFjdHMvRXJyb3JSZXNwb25zZUludGVyZmFjZSc7XG5cbi8qKlxuICogRXJyb3IgbWVzc2FnZS5cbiAqL1xuY29uc3QgUkVTUE9OU0VfSVNfTk9UX0pTT046IHN0cmluZyA9XG4gICAgJ1Jlc3BvbnNlIGlzIG5vdCBpbiBKU09OIGZvcm1hdC4gSXQgbWlnaHQgYmUgaW52YWxpZCB0b2tlbiBVUkwsIG5ldHdvcmsgZXJyb3IsIGludmFsaWQgcmVzcG9uc2UgZm9ybWF0IG9yIHNlcnZlci1zaWRlIGVycm9yLic7XG5cbi8qKlxuICogQGluaGVyaXRkb2NcbiAqL1xuZXhwb3J0IGRlZmF1bHQgY2xhc3MgaW1wbGVtZW50cyBBdXRob3JpemVySW50ZXJmYWNlIHtcbiAgICAvKipcbiAgICAgKiBGZXRjaGVyIHdyYXBwZXIuXG4gICAgICpcbiAgICAgKiBAaW50ZXJuYWxcbiAgICAgKi9cbiAgICBwcml2YXRlIHJlYWRvbmx5IHJlcXVlc3RzOiBDbGllbnRSZXF1ZXN0c0ludGVyZmFjZTtcblxuICAgIC8qKlxuICAgICAqIENvbnN0cnVjdG9yLlxuICAgICAqL1xuICAgIHB1YmxpYyBjb25zdHJ1Y3RvcihyZXF1ZXN0czogQ2xpZW50UmVxdWVzdHNJbnRlcmZhY2UpIHtcbiAgICAgICAgdGhpcy5yZXF1ZXN0cyA9IHJlcXVlc3RzO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEBpbmhlcml0ZG9jXG4gICAgICovXG4gICAgY29kZShhdXRoQ29kZTogc3RyaW5nLCByZWRpcmVjdFVyaT86IHN0cmluZyB8IHVuZGVmaW5lZCwgY2xpZW50SWQ/OiBzdHJpbmcgfCB1bmRlZmluZWQpOiBQcm9taXNlPFRva2VuSW50ZXJmYWNlPiB7XG4gICAgICAgIC8vIE5PVEU6IGl0IGltcGxlbWVudHMgc3RlcHMgNC4xLjMgYW5kIDQuMS40IG9mIHRoZSBzcGVjLlxuICAgICAgICAvLyBTdGVwcyA0LjEuMSBhbmQgNC4xLjIgKGdldHRpbmcgYXV0aG9yaXphdGlvbiBjb2RlKSBhcmUgb3V0c2lkZSBvZiB0aGUgaW1wbGVtZW50YXRpb24uXG4gICAgICAgIC8vIFRlY2huaWNhbGx5IHRob3NlIHN0ZXBzIGFyZSBwbGFpbiByZWRpcmVjdCB0byBhdXRob3JpemF0aW9uIHNlcnZlciAoQVMpIGFuZCByZWFkaW5nIHRoZSBjb2RlIGZyb20gVVJMIGluIGFcbiAgICAgICAgLy8gY2FsbGJhY2sgZnJvbSBBUy4gVGhleSBhcmUgdHJpdmlhbCBhbmQgdmVyeSBzcGVjaWZpYyB0byB0b29scyB1c2VkIGZvciBidWlsZGluZyB0aGUgYXBwLlxuICAgICAgICAvL1xuICAgICAgICAvLyBodHRwczovL3Rvb2xzLmlldGYub3JnL2h0bWwvcmZjNjc0OSNzZWN0aW9uLTQuMS4zIGFuZCBodHRwczovL3Rvb2xzLmlldGYub3JnL2h0bWwvcmZjNjc0OSNzZWN0aW9uLTQuMS40XG5cbiAgICAgICAgY29uc3QgYWRkQXV0aDogYm9vbGVhbiA9IChjbGllbnRJZCA9PT0gdW5kZWZpbmVkKTtcbiAgICAgICAgY29uc3QgZGF0YTogYW55ID0gYWRkQXV0aCA/XG4gICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgZ3JhbnRfdHlwZTogJ2F1dGhvcml6YXRpb25fY29kZScsXG4gICAgICAgICAgICAgICAgY29kZTogYXV0aENvZGVcbiAgICAgICAgICAgIH0gOiB7XG4gICAgICAgICAgICAgICAgZ3JhbnRfdHlwZTogJ2F1dGhvcml6YXRpb25fY29kZScsXG4gICAgICAgICAgICAgICAgY29kZTogYXV0aENvZGUsXG4gICAgICAgICAgICAgICAgY2xpZW50X2lkOiBjbGllbnRJZFxuICAgICAgICAgICAgfTtcbiAgICAgICAgaWYgKHJlZGlyZWN0VXJpICE9PSB1bmRlZmluZWQpIHtcbiAgICAgICAgICAgIGRhdGEucmVkaXJlY3RfdXJpID0gcmVkaXJlY3RVcmk7XG4gICAgICAgIH1cblxuICAgICAgICByZXR1cm4gdGhpcy5wYXJzZVRva2VuUmVzcG9uc2UodGhpcy5yZXF1ZXN0cy5zZW5kRm9ybShkYXRhLCBhZGRBdXRoKSk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQGluaGVyaXRkb2NcbiAgICAgKi9cbiAgICBwdWJsaWMgcGFzc3dvcmQodXNlck5hbWU6IHN0cmluZywgcGFzc3dvcmQ6IHN0cmluZywgc2NvcGU/OiBzdHJpbmcpOiBQcm9taXNlPFRva2VuSW50ZXJmYWNlPiB7XG4gICAgICAgIGNvbnN0IGRhdGEgPSBzY29wZSAhPT0gdW5kZWZpbmVkID9cbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICBncmFudF90eXBlOiAncGFzc3dvcmQnLFxuICAgICAgICAgICAgICAgIHVzZXJuYW1lOiB1c2VyTmFtZSxcbiAgICAgICAgICAgICAgICBwYXNzd29yZDogcGFzc3dvcmQsXG4gICAgICAgICAgICAgICAgc2NvcGU6IHNjb3BlLFxuICAgICAgICAgICAgfSA6IHtcbiAgICAgICAgICAgICAgICBncmFudF90eXBlOiAncGFzc3dvcmQnLFxuICAgICAgICAgICAgICAgIHVzZXJuYW1lOiB1c2VyTmFtZSxcbiAgICAgICAgICAgICAgICBwYXNzd29yZDogcGFzc3dvcmQsXG4gICAgICAgICAgICB9O1xuXG4gICAgICAgIHJldHVybiB0aGlzLnBhcnNlVG9rZW5SZXNwb25zZSh0aGlzLnJlcXVlc3RzLnNlbmRGb3JtKGRhdGEsIGZhbHNlKSk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQGluaGVyaXRkb2NcbiAgICAgKi9cbiAgICBwdWJsaWMgY2xpZW50KHNjb3BlPzogc3RyaW5nKTogUHJvbWlzZTxUb2tlbkludGVyZmFjZT4ge1xuICAgICAgICBjb25zdCBkYXRhID0gc2NvcGUgIT09IHVuZGVmaW5lZCA/XG4gICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgZ3JhbnRfdHlwZTogJ2NsaWVudF9jcmVkZW50aWFscycsXG4gICAgICAgICAgICAgICAgc2NvcGU6IHNjb3BlXG4gICAgICAgICAgICB9IDoge1xuICAgICAgICAgICAgICAgIGdyYW50X3R5cGU6ICdjbGllbnRfY3JlZGVudGlhbHMnXG4gICAgICAgICAgICB9O1xuXG4gICAgICAgIHJldHVybiB0aGlzLnBhcnNlVG9rZW5SZXNwb25zZSh0aGlzLnJlcXVlc3RzLnNlbmRGb3JtKGRhdGEsIHRydWUpKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBAaW5oZXJpdGRvY1xuICAgICAqL1xuICAgIHB1YmxpYyByZWZyZXNoKHJlZnJlc2hUb2tlbjogc3RyaW5nLCBzY29wZT86IHN0cmluZyk6IFByb21pc2U8VG9rZW5JbnRlcmZhY2U+IHtcbiAgICAgICAgY29uc3QgZGF0YSA9IHNjb3BlICE9PSB1bmRlZmluZWQgP1xuICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgIGdyYW50X3R5cGU6ICdyZWZyZXNoX3Rva2VuJyxcbiAgICAgICAgICAgICAgICByZWZyZXNoX3Rva2VuOiByZWZyZXNoVG9rZW4sXG4gICAgICAgICAgICAgICAgc2NvcGU6IHNjb3BlLFxuICAgICAgICAgICAgfSA6IHtcbiAgICAgICAgICAgICAgICBncmFudF90eXBlOiAncmVmcmVzaF90b2tlbicsXG4gICAgICAgICAgICAgICAgcmVmcmVzaF90b2tlbjogcmVmcmVzaFRva2VuLFxuICAgICAgICAgICAgfTtcblxuICAgICAgICByZXR1cm4gdGhpcy5wYXJzZVRva2VuUmVzcG9uc2UodGhpcy5yZXF1ZXN0cy5zZW5kRm9ybShkYXRhLCBmYWxzZSkpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIENvbW1vbiBjb2RlIGZvciBwYXJzaW5nIHRva2VuIHJlc3BvbnNlcy5cbiAgICAgKlxuICAgICAqIEBwYXJhbSByZXNwb25zZVByb21pc2VcbiAgICAgKi9cbiAgICBwcm90ZWN0ZWQgcGFyc2VUb2tlblJlc3BvbnNlKHJlc3BvbnNlUHJvbWlzZTogUHJvbWlzZTxSZXNwb25zZT4pOiBQcm9taXNlPFRva2VuSW50ZXJmYWNlPiB7XG4gICAgICAgIHJldHVybiByZXNwb25zZVByb21pc2VcbiAgICAgICAgICAgIC8vIGhlcmUgdGhlIHJlc3BvbnNlIGhhcyBvbmx5IEhUVFAgc3RhdHVzIGJ1dCB3ZSB3YW50IHJlc29sdmVkIEpTT04gYXMgd2VsbCBzby4uLlxuICAgICAgICAgICAgLnRoZW4ocmVzcG9uc2UgPT4gUHJvbWlzZS5hbGwoW1xuICAgICAgICAgICAgICAgIHJlc3BvbnNlLmpzb24oKSxcbiAgICAgICAgICAgICAgICBQcm9taXNlLnJlc29sdmUocmVzcG9uc2Uub2spLFxuICAgICAgICAgICAgXSkpXG4gICAgICAgICAgICAvLyAuLi4gbm93IHdlIGhhdmUgYm90aFxuICAgICAgICAgICAgLnRoZW4ocmVzdWx0cyA9PiB7XG4gICAgICAgICAgICAgICAgY29uc3QgW2pzb24sIGlzT2tdID0gcmVzdWx0cztcbiAgICAgICAgICAgICAgICBjb25zdCB0b2tlbjogVG9rZW5JbnRlcmZhY2UgPSBqc29uO1xuICAgICAgICAgICAgICAgIHJldHVybiBpc09rID09PSB0cnVlICYmIHRva2VuLmFjY2Vzc190b2tlbiAhPT0gdW5kZWZpbmVkICYmIHRva2VuLnRva2VuX3R5cGUgIT09IHVuZGVmaW5lZCA/XG4gICAgICAgICAgICAgICAgICAgIFByb21pc2UucmVzb2x2ZSh0b2tlbikgOlxuICAgICAgICAgICAgICAgICAgICBQcm9taXNlLnJlamVjdChuZXcgVG9rZW5FcnJvcig8RXJyb3JSZXNwb25zZUludGVyZmFjZT5qc29uKSk7XG4gICAgICAgICAgICB9KVxuICAgICAgICAgICAgLy8gcmV0dXJuIHRoZSBlcnJvciBmcm9tIHRoZSBibG9jayBhYm92ZSBvciByZXBvcnQgdGhlIHJlc3BvbnNlIHdhcyBub3QgSlNPTlxuICAgICAgICAgICAgLmNhdGNoKChlcnJvcjogYW55KSA9PiBQcm9taXNlLnJlamVjdChcbiAgICAgICAgICAgICAgICBlcnJvci5yZWFzb24gIT09IHVuZGVmaW5lZCA/IGVycm9yIDogbmV3IFR5cGVFcnJvcihSRVNQT05TRV9JU19OT1RfSlNPTilcbiAgICAgICAgICAgICkpO1xuICAgIH1cbn1cbiJdfQ==