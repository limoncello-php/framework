"use strict";
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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiQXV0aG9yaXplci5qcyIsInNvdXJjZVJvb3QiOiIvaG9tZS9uZW9tZXJ4L1Byb2plY3RzL2xpbW9uY2VsbG8vZnJhbWV3b3JrL2NvbXBvbmVudHMvT0F1dGhDbGllbnQvZGlzdC8iLCJzb3VyY2VzIjpbIkF1dGhvcml6ZXIudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7QUFHQSwyQ0FBc0M7QUFHdEM7O0dBRUc7QUFDSCxJQUFNLG9CQUFvQixHQUN0Qiw2SEFBNkgsQ0FBQztBQUVsSTs7R0FFRztBQUNIO0lBUUk7O09BRUc7SUFDSCxtQkFBbUIsUUFBaUM7UUFDaEQsSUFBSSxDQUFDLFFBQVEsR0FBRyxRQUFRLENBQUM7SUFDN0IsQ0FBQztJQUVEOztPQUVHO0lBQ0gsd0JBQUksR0FBSixVQUFLLFFBQWdCLEVBQUUsV0FBZ0MsRUFBRSxRQUE2QjtRQUNsRix5REFBeUQ7UUFDekQsd0ZBQXdGO1FBQ3hGLDZHQUE2RztRQUM3RywyRkFBMkY7UUFDM0YsRUFBRTtRQUNGLDBHQUEwRztRQUUxRyxJQUFNLE9BQU8sR0FBWSxDQUFDLFFBQVEsS0FBSyxTQUFTLENBQUMsQ0FBQztRQUNsRCxJQUFNLElBQUksR0FBUSxPQUFPLENBQUMsQ0FBQztZQUN2QjtnQkFDSSxVQUFVLEVBQUUsb0JBQW9CO2dCQUNoQyxJQUFJLEVBQUUsUUFBUTthQUNqQixDQUFDLENBQUMsQ0FBQztZQUNBLFVBQVUsRUFBRSxvQkFBb0I7WUFDaEMsSUFBSSxFQUFFLFFBQVE7WUFDZCxTQUFTLEVBQUUsUUFBUTtTQUN0QixDQUFDO1FBQ04sRUFBRSxDQUFDLENBQUMsV0FBVyxLQUFLLFNBQVMsQ0FBQyxDQUFDLENBQUM7WUFDNUIsSUFBSSxDQUFDLFlBQVksR0FBRyxXQUFXLENBQUM7UUFDcEMsQ0FBQztRQUVELE1BQU0sQ0FBQyxJQUFJLENBQUMsa0JBQWtCLENBQUMsSUFBSSxDQUFDLFFBQVEsQ0FBQyxRQUFRLENBQUMsSUFBSSxFQUFFLE9BQU8sQ0FBQyxDQUFDLENBQUM7SUFDMUUsQ0FB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c0NvbnRlbnQiOlsiaW1wb3J0IEF1dGhvcml6ZXJJbnRlcmZhY2UgZnJvbSAnLi9Db250cmFjdHMvQXV0aG9yaXplckludGVyZmFjZSc7XG5pbXBvcnQgVG9rZW5JbnRlcmZhY2UgZnJvbSAnLi9Db250cmFjdHMvVG9rZW5JbnRlcmZhY2UnO1xuaW1wb3J0IENsaWVudFJlcXVlc3RzSW50ZXJmYWNlIGZyb20gJy4vQ29udHJhY3RzL0NsaWVudFJlcXVlc3RzSW50ZXJmYWNlJztcbmltcG9ydCBUb2tlbkVycm9yIGZyb20gJy4vVG9rZW5FcnJvcic7XG5pbXBvcnQgRXJyb3JSZXNwb25zZUludGVyZmFjZSBmcm9tICcuL0NvbnRyYWN0cy9FcnJvclJlc3BvbnNlSW50ZXJmYWNlJztcblxuLyoqXG4gKiBFcnJvciBtZXNzYWdlLlxuICovXG5jb25zdCBSRVNQT05TRV9JU19OT1RfSlNPTjogc3RyaW5nID1cbiAgICAnUmVzcG9uc2UgaXMgbm90IGluIEpTT04gZm9ybWF0LiBJdCBtaWdodCBiZSBpbnZhbGlkIHRva2VuIFVSTCwgbmV0d29yayBlcnJvciwgaW52YWxpZCByZXNwb25zZSBmb3JtYXQgb3Igc2VydmVyLXNpZGUgZXJyb3IuJztcblxuLyoqXG4gKiBAaW5oZXJpdGRvY1xuICovXG5leHBvcnQgZGVmYXVsdCBjbGFzcyBpbXBsZW1lbnRzIEF1dGhvcml6ZXJJbnRlcmZhY2Uge1xuICAgIC8qKlxuICAgICAqIEZldGNoZXIgd3JhcHBlci5cbiAgICAgKlxuICAgICAqIEBpbnRlcm5hbFxuICAgICAqL1xuICAgIHByaXZhdGUgcmVhZG9ubHkgcmVxdWVzdHM6IENsaWVudFJlcXVlc3RzSW50ZXJmYWNlO1xuXG4gICAgLyoqXG4gICAgICogQ29uc3RydWN0b3IuXG4gICAgICovXG4gICAgcHVibGljIGNvbnN0cnVjdG9yKHJlcXVlc3RzOiBDbGllbnRSZXF1ZXN0c0ludGVyZmFjZSkge1xuICAgICAgICB0aGlzLnJlcXVlc3RzID0gcmVxdWVzdHM7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQGluaGVyaXRkb2NcbiAgICAgKi9cbiAgICBjb2RlKGF1dGhDb2RlOiBzdHJpbmcsIHJlZGlyZWN0VXJpPzogc3RyaW5nIHwgdW5kZWZpbmVkLCBjbGllbnRJZD86IHN0cmluZyB8IHVuZGVmaW5lZCk6IFByb21pc2U8VG9rZW5JbnRlcmZhY2U+IHtcbiAgICAgICAgLy8gTk9URTogaXQgaW1wbGVtZW50cyBzdGVwcyA0LjEuMyBhbmQgNC4xLjQgb2YgdGhlIHNwZWMuXG4gICAgICAgIC8vIFN0ZXBzIDQuMS4xIGFuZCA0LjEuMiAoZ2V0dGluZyBhdXRob3JpemF0aW9uIGNvZGUpIGFyZSBvdXRzaWRlIG9mIHRoZSBpbXBsZW1lbnRhdGlvbi5cbiAgICAgICAgLy8gVGVjaG5pY2FsbHkgdGhvc2Ugc3RlcHMgYXJlIHBsYWluIHJlZGlyZWN0IHRvIGF1dGhvcml6YXRpb24gc2VydmVyIChBUykgYW5kIHJlYWRpbmcgdGhlIGNvZGUgZnJvbSBVUkwgaW4gYVxuICAgICAgICAvLyBjYWxsYmFjayBmcm9tIEFTLiBUaGV5IGFyZSB0cml2aWFsIGFuZCB2ZXJ5IHNwZWNpZmljIHRvIHRvb2xzIHVzZWQgZm9yIGJ1aWxkaW5nIHRoZSBhcHAuXG4gICAgICAgIC8vXG4gICAgICAgIC8vIGh0dHBzOi8vdG9vbHMuaWV0Zi5vcmcvaHRtbC9yZmM2NzQ5I3NlY3Rpb24tNC4xLjMgYW5kIGh0dHBzOi8vdG9vbHMuaWV0Zi5vcmcvaHRtbC9yZmM2NzQ5I3NlY3Rpb24tNC4xLjRcblxuICAgICAgICBjb25zdCBhZGRBdXRoOiBib29sZWFuID0gKGNsaWVudElkID09PSB1bmRlZmluZWQpO1xuICAgICAgICBjb25zdCBkYXRhOiBhbnkgPSBhZGRBdXRoID9cbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICBncmFudF90eXBlOiAnYXV0aG9yaXphdGlvbl9jb2RlJyxcbiAgICAgICAgICAgICAgICBjb2RlOiBhdXRoQ29kZVxuICAgICAgICAgICAgfSA6IHtcbiAgICAgICAgICAgICAgICBncmFudF90eXBlOiAnYXV0aG9yaXphdGlvbl9jb2RlJyxcbiAgICAgICAgICAgICAgICBjb2RlOiBhdXRoQ29kZSxcbiAgICAgICAgICAgICAgICBjbGllbnRfaWQ6IGNsaWVudElkXG4gICAgICAgICAgICB9O1xuICAgICAgICBpZiAocmVkaXJlY3RVcmkgIT09IHVuZGVmaW5lZCkge1xuICAgICAgICAgICAgZGF0YS5yZWRpcmVjdF91cmkgPSByZWRpcmVjdFVyaTtcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiB0aGlzLnBhcnNlVG9rZW5SZXNwb25zZSh0aGlzLnJlcXVlc3RzLnNlbmRGb3JtKGRhdGEsIGFkZEF1dGgpKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBAaW5oZXJpdGRvY1xuICAgICAqL1xuICAgIHB1YmxpYyBwYXNzd29yZCh1c2VyTmFtZTogc3RyaW5nLCBwYXNzd29yZDogc3RyaW5nLCBzY29wZT86IHN0cmluZyk6IFByb21pc2U8VG9rZW5JbnRlcmZhY2U+IHtcbiAgICAgICAgY29uc3QgZGF0YSA9IHNjb3BlICE9PSB1bmRlZmluZWQgP1xuICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgIGdyYW50X3R5cGU6ICdwYXNzd29yZCcsXG4gICAgICAgICAgICAgICAgdXNlcm5hbWU6IHVzZXJOYW1lLFxuICAgICAgICAgICAgICAgIHBhc3N3b3JkOiBwYXNzd29yZCxcbiAgICAgICAgICAgICAgICBzY29wZTogc2NvcGUsXG4gICAgICAgICAgICB9IDoge1xuICAgICAgICAgICAgICAgIGdyYW50X3R5cGU6ICdwYXNzd29yZCcsXG4gICAgICAgICAgICAgICAgdXNlcm5hbWU6IHVzZXJOYW1lLFxuICAgICAgICAgICAgICAgIHBhc3N3b3JkOiBwYXNzd29yZCxcbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgcmV0dXJuIHRoaXMucGFyc2VUb2tlblJlc3BvbnNlKHRoaXMucmVxdWVzdHMuc2VuZEZvcm0oZGF0YSwgZmFsc2UpKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBAaW5oZXJpdGRvY1xuICAgICAqL1xuICAgIHB1YmxpYyBjbGllbnQoc2NvcGU/OiBzdHJpbmcpOiBQcm9taXNlPFRva2VuSW50ZXJmYWNlPiB7XG4gICAgICAgIGNvbnN0IGRhdGEgPSBzY29wZSAhPT0gdW5kZWZpbmVkID9cbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICBncmFudF90eXBlOiAnY2xpZW50X2NyZWRlbnRpYWxzJyxcbiAgICAgICAgICAgICAgICBzY29wZTogc2NvcGVcbiAgICAgICAgICAgIH0gOiB7XG4gICAgICAgICAgICAgICAgZ3JhbnRfdHlwZTogJ2NsaWVudF9jcmVkZW50aWFscydcbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgcmV0dXJuIHRoaXMucGFyc2VUb2tlblJlc3BvbnNlKHRoaXMucmVxdWVzdHMuc2VuZEZvcm0oZGF0YSwgdHJ1ZSkpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEBpbmhlcml0ZG9jXG4gICAgICovXG4gICAgcHVibGljIHJlZnJlc2gocmVmcmVzaFRva2VuOiBzdHJpbmcsIHNjb3BlPzogc3RyaW5nKTogUHJvbWlzZTxUb2tlbkludGVyZmFjZT4ge1xuICAgICAgICBjb25zdCBkYXRhID0gc2NvcGUgIT09IHVuZGVmaW5lZCA/XG4gICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgZ3JhbnRfdHlwZTogJ3JlZnJlc2hfdG9rZW4nLFxuICAgICAgICAgICAgICAgIHJlZnJlc2hfdG9rZW46IHJlZnJlc2hUb2tlbixcbiAgICAgICAgICAgICAgICBzY29wZTogc2NvcGUsXG4gICAgICAgICAgICB9IDoge1xuICAgICAgICAgICAgICAgIGdyYW50X3R5cGU6ICdyZWZyZXNoX3Rva2VuJyxcbiAgICAgICAgICAgICAgICByZWZyZXNoX3Rva2VuOiByZWZyZXNoVG9rZW4sXG4gICAgICAgICAgICB9O1xuXG4gICAgICAgIHJldHVybiB0aGlzLnBhcnNlVG9rZW5SZXNwb25zZSh0aGlzLnJlcXVlc3RzLnNlbmRGb3JtKGRhdGEsIGZhbHNlKSk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQ29tbW9uIGNvZGUgZm9yIHBhcnNpbmcgdG9rZW4gcmVzcG9uc2VzLlxuICAgICAqXG4gICAgICogQHBhcmFtIHJlc3BvbnNlUHJvbWlzZVxuICAgICAqL1xuICAgIHByb3RlY3RlZCBwYXJzZVRva2VuUmVzcG9uc2UocmVzcG9uc2VQcm9taXNlOiBQcm9taXNlPFJlc3BvbnNlPik6IFByb21pc2U8VG9rZW5JbnRlcmZhY2U+IHtcbiAgICAgICAgcmV0dXJuIHJlc3BvbnNlUHJvbWlzZVxuICAgICAgICAgICAgLy8gaGVyZSB0aGUgcmVzcG9uc2UgaGFzIG9ubHkgSFRUUCBzdGF0dXMgYnV0IHdlIHdhbnQgcmVzb2x2ZWQgSlNPTiBhcyB3ZWxsIHNvLi4uXG4gICAgICAgICAgICAudGhlbihyZXNwb25zZSA9PiBQcm9taXNlLmFsbChbXG4gICAgICAgICAgICAgICAgcmVzcG9uc2UuanNvbigpLFxuICAgICAgICAgICAgICAgIFByb21pc2UucmVzb2x2ZShyZXNwb25zZS5vayksXG4gICAgICAgICAgICBdKSlcbiAgICAgICAgICAgIC8vIC4uLiBub3cgd2UgaGF2ZSBib3RoXG4gICAgICAgICAgICAudGhlbihyZXN1bHRzID0+IHtcbiAgICAgICAgICAgICAgICBjb25zdCBbanNvbiwgaXNPa10gPSByZXN1bHRzO1xuICAgICAgICAgICAgICAgIGNvbnN0IHRva2VuOiBUb2tlbkludGVyZmFjZSA9IGpzb247XG4gICAgICAgICAgICAgICAgcmV0dXJuIGlzT2sgPT09IHRydWUgJiYgdG9rZW4uYWNjZXNzX3Rva2VuICE9PSB1bmRlZmluZWQgJiYgdG9rZW4udG9rZW5fdHlwZSAhPT0gdW5kZWZpbmVkID9cbiAgICAgICAgICAgICAgICAgICAgUHJvbWlzZS5yZXNvbHZlKHRva2VuKSA6XG4gICAgICAgICAgICAgICAgICAgIFByb21pc2UucmVqZWN0KG5ldyBUb2tlbkVycm9yKDxFcnJvclJlc3BvbnNlSW50ZXJmYWNlPmpzb24pKTtcbiAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAvLyByZXR1cm4gdGhlIGVycm9yIGZyb20gdGhlIGJsb2NrIGFib3ZlIG9yIHJlcG9ydCB0aGUgcmVzcG9uc2Ugd2FzIG5vdCBKU09OXG4gICAgICAgICAgICAuY2F0Y2goKGVycm9yOiBhbnkpID0+IFByb21pc2UucmVqZWN0KFxuICAgICAgICAgICAgICAgIGVycm9yLnJlYXNvbiAhPT0gdW5kZWZpbmVkID8gZXJyb3IgOiBuZXcgVHlwZUVycm9yKFJFU1BPTlNFX0lTX05PVF9KU09OKVxuICAgICAgICAgICAgKSk7XG4gICAgfVxufVxuIl19