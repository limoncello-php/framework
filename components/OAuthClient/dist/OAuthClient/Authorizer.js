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
var Authorizer = /** @class */ (function () {
    /**
     * Constructor.
     */
    function Authorizer(requests) {
        this.requests = requests;
    }
    /**
     * @inheritdoc
     */
    Authorizer.prototype.code = function (authCode, redirectUri, clientId) {
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
    Authorizer.prototype.password = function (userName, password, scope) {
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
    Authorizer.prototype.client = function (scope) {
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
    Authorizer.prototype.refresh = function (refreshToken, scope) {
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
     *
     * @internal
     */
    Authorizer.prototype.parseTokenResponse = function (responsePromise) {
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
                Promise.reject(new TokenError_1.TokenError(json));
        })
            .catch(function (error) { return Promise.reject(error.reason !== undefined ? error : new TypeError(RESPONSE_IS_NOT_JSON)); });
    };
    return Authorizer;
}());
exports.Authorizer = Authorizer;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiQXV0aG9yaXplci5qcyIsInNvdXJjZVJvb3QiOiIvaG9tZS9uZW9tZXJ4L1Byb2plY3RzL2xpbW9uY2VsbG8vZnJhbWV3b3JrL2NvbXBvbmVudHMvT0F1dGhDbGllbnQvZGlzdC8iLCJzb3VyY2VzIjpbIk9BdXRoQ2xpZW50L0F1dGhvcml6ZXIudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IjtBQUFBOzs7Ozs7Ozs7Ozs7OztHQWNHOztBQU1ILDJDQUEwQztBQUUxQzs7R0FFRztBQUNILElBQU0sb0JBQW9CLEdBQ3RCLDZIQUE2SCxDQUFDO0FBRWxJOztHQUVHO0FBQ0g7SUFRSTs7T0FFRztJQUNILG9CQUFtQixRQUFpQztRQUNoRCxJQUFJLENBQUMsUUFBUSxHQUFHLFFBQVEsQ0FBQztJQUM3QixDQUFDO0lBRUQ7O09BRUc7SUFDSCx5QkFBSSxHQUFKLFVBQUssUUFBZ0IsRUFBRSxXQUFnQyxFQUFFLFFBQTZCO1FBQ2xGLHlEQUF5RDtRQUN6RCx3RkFBd0Y7UUFDeEYsNkdBQTZHO1FBQzdHLDJGQUEyRjtRQUMzRixFQUFFO1FBQ0YsMEdBQTBHO1FBRTFHLElBQU0sT0FBTyxHQUFZLENBQUMsUUFBUSxLQUFLLFNBQVMsQ0FBQyxDQUFDO1FBQ2xELElBQU0sSUFBSSxHQUFRLE9BQU8sQ0FBQyxDQUFDO1lBQ3ZCO2dCQUNJLFVBQVUsRUFBRSxvQkFBb0I7Z0JBQ2hDLElBQUksRUFBRSxRQUFRO2FBQ2pCLENBQUMsQ0FBQyxDQUFDO1lBQ0EsVUFBVSxFQUFFLG9CQUFvQjtZQUNoQyxJQUFJLEVBQUUsUUFBUTtZQUNkLFNBQVMsRUFBRSxRQUFRO1NBQ3RCLENBQUM7UUFDTixFQUFFLENBQUMsQ0FBQyxXQUFXLEtBQUssU0FBUyxDQUFDLENBQUMsQ0FBQztZQUM1QixJQUFJLENBQUMsWUFBWSxHQUFHLFdBQVcsQ0FBQztRQUNwQyxDQUFDO1FBRUQsTUFBTSxDQUFDLElBQUksQ0FBQyxrQkFBa0IsQ0FBQyxJQUFJLENBQUMsUUFBUSxDQUFDLFFBQVEsQ0FBQyxJQUFJ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LGlCQUFDO0FBQUQsQ0FBQyxBQXpIRCxJQXlIQztBQXpIWSxnQ0FBVSIsInNvdXJjZXNDb250ZW50IjpbIi8qKlxuICogQ29weXJpZ2h0IDIwMTUtMjAxNyBpbmZvQG5lb21lcnguY29tXG4gKlxuICogTGljZW5zZWQgdW5kZXIgdGhlIEFwYWNoZSBMaWNlbnNlLCBWZXJzaW9uIDIuMCAodGhlIFwiTGljZW5zZVwiKTtcbiAqIHlvdSBtYXkgbm90IHVzZSB0aGlzIGZpbGUgZXhjZXB0IGluIGNvbXBsaWFuY2Ugd2l0aCB0aGUgTGljZW5zZS5cbiAqIFlvdSBtYXkgb2J0YWluIGEgY29weSBvZiB0aGUgTGljZW5zZSBhdFxuICpcbiAqIGh0dHA6Ly93d3cuYXBhY2hlLm9yZy9saWNlbnNlcy9MSUNFTlNFLTIuMFxuICpcbiAqIFVubGVzcyByZXF1aXJlZCBieSBhcHBsaWNhYmxlIGxhdyBvciBhZ3JlZWQgdG8gaW4gd3JpdGluZywgc29mdHdhcmVcbiAqIGRpc3RyaWJ1dGVkIHVuZGVyIHRoZSBMaWNlbnNlIGlzIGRpc3RyaWJ1dGVkIG9uIGFuIFwiQVMgSVNcIiBCQVNJUyxcbiAqIFdJVEhPVVQgV0FSUkFOVElFUyBPUiBDT05ESVRJT05TIE9GIEFOWSBLSU5ELCBlaXRoZXIgZXhwcmVzcyBvciBpbXBsaWVkLlxuICogU2VlIHRoZSBMaWNlbnNlIGZvciB0aGUgc3BlY2lmaWMgbGFuZ3VhZ2UgZ292ZXJuaW5nIHBlcm1pc3Npb25zIGFuZFxuICogbGltaXRhdGlvbnMgdW5kZXIgdGhlIExpY2Vuc2UuXG4gKi9cblxuaW1wb3J0IHsgQXV0aG9yaXplckludGVyZmFjZSB9IGZyb20gJy4vLi4vQ29udHJhY3RzL0F1dGhvcml6ZXJJbnRlcmZhY2UnO1xuaW1wb3J0IHsgQ2xpZW50UmVxdWVzdHNJbnRlcmZhY2UgfSBmcm9tICcuLy4uL0NvbnRyYWN0cy9DbGllbnRSZXF1ZXN0c0ludGVyZmFjZSc7XG5pbXBvcnQgeyBFcnJvclJlc3BvbnNlSW50ZXJmYWNlIH0gZnJvbSAnLi8uLi9Db250cmFjdHMvRXJyb3JSZXNwb25zZUludGVyZmFjZSc7XG5pbXBvcnQgeyBUb2tlbkludGVyZmFjZSB9IGZyb20gJy4vLi4vQ29udHJhY3RzL1Rva2VuSW50ZXJmYWNlJztcbmltcG9ydCB7IFRva2VuRXJyb3IgfSBmcm9tICcuL1Rva2VuRXJyb3InO1xuXG4vKipcbiAqIEVycm9yIG1lc3NhZ2UuXG4gKi9cbmNvbnN0IFJFU1BPTlNFX0lTX05PVF9KU09OOiBzdHJpbmcgPVxuICAgICdSZXNwb25zZSBpcyBub3QgaW4gSlNPTiBmb3JtYXQuIEl0IG1pZ2h0IGJlIGludmFsaWQgdG9rZW4gVVJMLCBuZXR3b3JrIGVycm9yLCBpbnZhbGlkIHJlc3BvbnNlIGZvcm1hdCBvciBzZXJ2ZXItc2lkZSBlcnJvci4nO1xuXG4vKipcbiAqIEBpbmhlcml0ZG9jXG4gKi9cbmV4cG9ydCBjbGFzcyBBdXRob3JpemVyIGltcGxlbWVudHMgQXV0aG9yaXplckludGVyZmFjZSB7XG4gICAgLyoqXG4gICAgICogRmV0Y2hlciB3cmFwcGVyLlxuICAgICAqXG4gICAgICogQGludGVybmFsXG4gICAgICovXG4gICAgcHJpdmF0ZSByZWFkb25seSByZXF1ZXN0czogQ2xpZW50UmVxdWVzdHNJbnRlcmZhY2U7XG5cbiAgICAvKipcbiAgICAgKiBDb25zdHJ1Y3Rvci5cbiAgICAgKi9cbiAgICBwdWJsaWMgY29uc3RydWN0b3IocmVxdWVzdHM6IENsaWVudFJlcXVlc3RzSW50ZXJmYWNlKSB7XG4gICAgICAgIHRoaXMucmVxdWVzdHMgPSByZXF1ZXN0cztcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBAaW5oZXJpdGRvY1xuICAgICAqL1xuICAgIGNvZGUoYXV0aENvZGU6IHN0cmluZywgcmVkaXJlY3RVcmk/OiBzdHJpbmcgfCB1bmRlZmluZWQsIGNsaWVudElkPzogc3RyaW5nIHwgdW5kZWZpbmVkKTogUHJvbWlzZTxUb2tlbkludGVyZmFjZT4ge1xuICAgICAgICAvLyBOT1RFOiBpdCBpbXBsZW1lbnRzIHN0ZXBzIDQuMS4zIGFuZCA0LjEuNCBvZiB0aGUgc3BlYy5cbiAgICAgICAgLy8gU3RlcHMgNC4xLjEgYW5kIDQuMS4yIChnZXR0aW5nIGF1dGhvcml6YXRpb24gY29kZSkgYXJlIG91dHNpZGUgb2YgdGhlIGltcGxlbWVudGF0aW9uLlxuICAgICAgICAvLyBUZWNobmljYWxseSB0aG9zZSBzdGVwcyBhcmUgcGxhaW4gcmVkaXJlY3QgdG8gYXV0aG9yaXphdGlvbiBzZXJ2ZXIgKEFTKSBhbmQgcmVhZGluZyB0aGUgY29kZSBmcm9tIFVSTCBpbiBhXG4gICAgICAgIC8vIGNhbGxiYWNrIGZyb20gQVMuIFRoZXkgYXJlIHRyaXZpYWwgYW5kIHZlcnkgc3BlY2lmaWMgdG8gdG9vbHMgdXNlZCBmb3IgYnVpbGRpbmcgdGhlIGFwcC5cbiAgICAgICAgLy9cbiAgICAgICAgLy8gaHR0cHM6Ly90b29scy5pZXRmLm9yZy9odG1sL3JmYzY3NDkjc2VjdGlvbi00LjEuMyBhbmQgaHR0cHM6Ly90b29scy5pZXRmLm9yZy9odG1sL3JmYzY3NDkjc2VjdGlvbi00LjEuNFxuXG4gICAgICAgIGNvbnN0IGFkZEF1dGg6IGJvb2xlYW4gPSAoY2xpZW50SWQgPT09IHVuZGVmaW5lZCk7XG4gICAgICAgIGNvbnN0IGRhdGE6IGFueSA9IGFkZEF1dGggP1xuICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgIGdyYW50X3R5cGU6ICdhdXRob3JpemF0aW9uX2NvZGUnLFxuICAgICAgICAgICAgICAgIGNvZGU6IGF1dGhDb2RlXG4gICAgICAgICAgICB9IDoge1xuICAgICAgICAgICAgICAgIGdyYW50X3R5cGU6ICdhdXRob3JpemF0aW9uX2NvZGUnLFxuICAgICAgICAgICAgICAgIGNvZGU6IGF1dGhDb2RlLFxuICAgICAgICAgICAgICAgIGNsaWVudF9pZDogY2xpZW50SWRcbiAgICAgICAgICAgIH07XG4gICAgICAgIGlmIChyZWRpcmVjdFVyaSAhPT0gdW5kZWZpbmVkKSB7XG4gICAgICAgICAgICBkYXRhLnJlZGlyZWN0X3VyaSA9IHJlZGlyZWN0VXJpO1xuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuIHRoaXMucGFyc2VUb2tlblJlc3BvbnNlKHRoaXMucmVxdWVzdHMuc2VuZEZvcm0oZGF0YSwgYWRkQXV0aCkpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEBpbmhlcml0ZG9jXG4gICAgICovXG4gICAgcHVibGljIHBhc3N3b3JkKHVzZXJOYW1lOiBzdHJpbmcsIHBhc3N3b3JkOiBzdHJpbmcsIHNjb3BlPzogc3RyaW5nKTogUHJvbWlzZTxUb2tlbkludGVyZmFjZT4ge1xuICAgICAgICBjb25zdCBkYXRhID0gc2NvcGUgIT09IHVuZGVmaW5lZCA/XG4gICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgZ3JhbnRfdHlwZTogJ3Bhc3N3b3JkJyxcbiAgICAgICAgICAgICAgICB1c2VybmFtZTogdXNlck5hbWUsXG4gICAgICAgICAgICAgICAgcGFzc3dvcmQ6IHBhc3N3b3JkLFxuICAgICAgICAgICAgICAgIHNjb3BlOiBzY29wZSxcbiAgICAgICAgICAgIH0gOiB7XG4gICAgICAgICAgICAgICAgZ3JhbnRfdHlwZTogJ3Bhc3N3b3JkJyxcbiAgICAgICAgICAgICAgICB1c2VybmFtZTogdXNlck5hbWUsXG4gICAgICAgICAgICAgICAgcGFzc3dvcmQ6IHBhc3N3b3JkLFxuICAgICAgICAgICAgfTtcblxuICAgICAgICByZXR1cm4gdGhpcy5wYXJzZVRva2VuUmVzcG9uc2UodGhpcy5yZXF1ZXN0cy5zZW5kRm9ybShkYXRhLCBmYWxzZSkpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEBpbmhlcml0ZG9jXG4gICAgICovXG4gICAgcHVibGljIGNsaWVudChzY29wZT86IHN0cmluZyk6IFByb21pc2U8VG9rZW5JbnRlcmZhY2U+IHtcbiAgICAgICAgY29uc3QgZGF0YSA9IHNjb3BlICE9PSB1bmRlZmluZWQgP1xuICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgIGdyYW50X3R5cGU6ICdjbGllbnRfY3JlZGVudGlhbHMnLFxuICAgICAgICAgICAgICAgIHNjb3BlOiBzY29wZVxuICAgICAgICAgICAgfSA6IHtcbiAgICAgICAgICAgICAgICBncmFudF90eXBlOiAnY2xpZW50X2NyZWRlbnRpYWxzJ1xuICAgICAgICAgICAgfTtcblxuICAgICAgICByZXR1cm4gdGhpcy5wYXJzZVRva2VuUmVzcG9uc2UodGhpcy5yZXF1ZXN0cy5zZW5kRm9ybShkYXRhLCB0cnVlKSk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQGluaGVyaXRkb2NcbiAgICAgKi9cbiAgICBwdWJsaWMgcmVmcmVzaChyZWZyZXNoVG9rZW46IHN0cmluZywgc2NvcGU/OiBzdHJpbmcpOiBQcm9taXNlPFRva2VuSW50ZXJmYWNlPiB7XG4gICAgICAgIGNvbnN0IGRhdGEgPSBzY29wZSAhPT0gdW5kZWZpbmVkID9cbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICBncmFudF90eXBlOiAncmVmcmVzaF90b2tlbicsXG4gICAgICAgICAgICAgICAgcmVmcmVzaF90b2tlbjogcmVmcmVzaFRva2VuLFxuICAgICAgICAgICAgICAgIHNjb3BlOiBzY29wZSxcbiAgICAgICAgICAgIH0gOiB7XG4gICAgICAgICAgICAgICAgZ3JhbnRfdHlwZTogJ3JlZnJlc2hfdG9rZW4nLFxuICAgICAgICAgICAgICAgIHJlZnJlc2hfdG9rZW46IHJlZnJlc2hUb2tlbixcbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgcmV0dXJuIHRoaXMucGFyc2VUb2tlblJlc3BvbnNlKHRoaXMucmVxdWVzdHMuc2VuZEZvcm0oZGF0YSwgZmFsc2UpKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBDb21tb24gY29kZSBmb3IgcGFyc2luZyB0b2tlbiByZXNwb25zZXMuXG4gICAgICpcbiAgICAgKiBAcGFyYW0gcmVzcG9uc2VQcm9taXNlXG4gICAgICpcbiAgICAgKiBAaW50ZXJuYWxcbiAgICAgKi9cbiAgICBwcm90ZWN0ZWQgcGFyc2VUb2tlblJlc3BvbnNlKHJlc3BvbnNlUHJvbWlzZTogUHJvbWlzZTxSZXNwb25zZT4pOiBQcm9taXNlPFRva2VuSW50ZXJmYWNlPiB7XG4gICAgICAgIHJldHVybiByZXNwb25zZVByb21pc2VcbiAgICAgICAgICAgIC8vIGhlcmUgdGhlIHJlc3BvbnNlIGhhcyBvbmx5IEhUVFAgc3RhdHVzIGJ1dCB3ZSB3YW50IHJlc29sdmVkIEpTT04gYXMgd2VsbCBzby4uLlxuICAgICAgICAgICAgLnRoZW4ocmVzcG9uc2UgPT4gUHJvbWlzZS5hbGwoW1xuICAgICAgICAgICAgICAgIHJlc3BvbnNlLmpzb24oKSxcbiAgICAgICAgICAgICAgICBQcm9taXNlLnJlc29sdmUocmVzcG9uc2Uub2spLFxuICAgICAgICAgICAgXSkpXG4gICAgICAgICAgICAvLyAuLi4gbm93IHdlIGhhdmUgYm90aFxuICAgICAgICAgICAgLnRoZW4ocmVzdWx0cyA9PiB7XG4gICAgICAgICAgICAgICAgY29uc3QgW2pzb24sIGlzT2tdID0gcmVzdWx0cztcbiAgICAgICAgICAgICAgICBjb25zdCB0b2tlbjogVG9rZW5JbnRlcmZhY2UgPSBqc29uO1xuICAgICAgICAgICAgICAgIHJldHVybiBpc09rID09PSB0cnVlICYmIHRva2VuLmFjY2Vzc190b2tlbiAhPT0gdW5kZWZpbmVkICYmIHRva2VuLnRva2VuX3R5cGUgIT09IHVuZGVmaW5lZCA/XG4gICAgICAgICAgICAgICAgICAgIFByb21pc2UucmVzb2x2ZSh0b2tlbikgOlxuICAgICAgICAgICAgICAgICAgICBQcm9taXNlLnJlamVjdChuZXcgVG9rZW5FcnJvcig8RXJyb3JSZXNwb25zZUludGVyZmFjZT5qc29uKSk7XG4gICAgICAgICAgICB9KVxuICAgICAgICAgICAgLy8gcmV0dXJuIHRoZSBlcnJvciBmcm9tIHRoZSBibG9jayBhYm92ZSBvciByZXBvcnQgdGhlIHJlc3BvbnNlIHdhcyBub3QgSlNPTlxuICAgICAgICAgICAgLmNhdGNoKChlcnJvcjogYW55KSA9PiBQcm9taXNlLnJlamVjdChcbiAgICAgICAgICAgICAgICBlcnJvci5yZWFzb24gIT09IHVuZGVmaW5lZCA/IGVycm9yIDogbmV3IFR5cGVFcnJvcihSRVNQT05TRV9JU19OT1RfSlNPTilcbiAgICAgICAgICAgICkpO1xuICAgIH1cbn1cbiJdfQ==