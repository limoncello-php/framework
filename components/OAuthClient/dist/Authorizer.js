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
            return isOk === true ?
                Promise.resolve(json) :
                Promise.reject(new TokenError_1.default(json));
        })
            .catch(function (error) { return Promise.reject(error.reason !== undefined ? error : new TypeError(RESPONSE_IS_NOT_JSON)); });
    };
    return default_1;
}());
exports.default = default_1;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiQXV0aG9yaXplci5qcyIsInNvdXJjZVJvb3QiOiIvaG9tZS9uZW9tZXJ4L1Byb2plY3RzL2xpbW9uY2VsbG8vZnJhbWV3b3JrL2NvbXBvbmVudHMvT0F1dGhDbGllbnQvZGlzdC8iLCJzb3VyY2VzIjpbIkF1dGhvcml6ZXIudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7QUFHQSwyQ0FBc0M7QUFHdEM7O0dBRUc7QUFDSCxJQUFNLG9CQUFvQixHQUN0Qiw2SEFBNkgsQ0FBQztBQUVsSTs7R0FFRztBQUNIO0lBUUk7O09BRUc7SUFDSCxtQkFBbUIsUUFBaUM7UUFDaEQsSUFBSSxDQUFDLFFBQVEsR0FBRyxRQUFRLENBQUM7SUFDN0IsQ0FBQztJQUVEOztPQUVHO0lBQ0gsd0JBQUksR0FBSixVQUFLLFFBQWdCLEVBQUUsV0FBZ0MsRUFBRSxRQUE2QjtRQUNsRix5REFBeUQ7UUFDekQsd0ZBQXdGO1FBQ3hGLDZHQUE2RztRQUM3RywyRkFBMkY7UUFDM0YsRUFBRTtRQUNGLDBHQUEwRztRQUUxRyxJQUFNLE9BQU8sR0FBWSxDQUFDLFFBQVEsS0FBSyxTQUFTLENBQUMsQ0FBQztRQUNsRCxJQUFNLElBQUksR0FBUSxPQUFPLENBQUMsQ0FBQztZQUN2QjtnQkFDSSxVQUFVLEVBQUUsb0JBQW9CO2dCQUNoQyxJQUFJLEVBQUUsUUFBUTthQUNqQixDQUFDLENBQUMsQ0FBQztZQUNBLFVBQVUsRUFBRSxvQkFBb0I7WUFDaEMsSUFBSSxFQUFFLFFBQVE7WUFDZCxTQUFTLEVBQUUsUUFBUTtTQUN0QixDQUFDO1FBQ04sRUFBRSxDQUFDLENBQUMsV0FBVyxLQUFLLFNBQVMsQ0FBQyxDQUFDLENBQUM7WUFDNUIsSUFBSSxDQUFDLFlBQVksR0FBRyxXQUFXLENBQUM7UUFDcEMsQ0FBQztRQUVELE1BQU0sQ0FBQyxJQUFJLENBQUMsa0JBQWtCLENBQUMsSUFBSSxDQUFDLFFBQVEsQ0FBQyxRQUFRLENBQUMsSUFBSSxFQUFFLE9BQU8sQ0FBQyxDQUFDLENBQUM7SUFDMUUsQ0FBQztJQUVEOztPQUVHO0lBQ0ksNEJBQVEsR0FBZixVQUFnQixRQUFnQixFQUFFLFFBQWdCLEVBQUUsS0FBYztRQUM5RCxJQUFNLElBQUksR0FBRyxLQUFLLEtBQUssU0FBUyxDQUFDLENBQUM7WUFDOUI7Z0JBQ0ksVUFBVSxFQUFFLFVBQVU7Z0JBQ3RCLFFBQVEsRUFBRSxRQUFRO2dCQUNsQixRQUFRLEVBQUUsUUFBUTtnQkFDbEIsS0FBSyxFQUFFLEtBQUs7YUFDZixDQUFDLENBQUMsQ0FBQztZQUNBLFVBQVUsRUFBRSxVQUFVO1lBQ3RCLFFBQVEsRUFBRSxRQUFRO1lBQ2xCLFFBQVEsRUFBRSxRQUFRO1NBQ3JCLENBQUM7UUFFTixNQUFNLENBQUMsSUFBSSxDQUFDLGtCQUFrQixDQUFDLElBQUksQ0FBQyxRQUFRLENBQUMsUUFBUSxDQUFDLElBQUksRUFBRSxLQUFLLENBQUMsQ0FBQyxDQUFDO0lBQ3hFLENBQUM7SUFFRDs7T0FFRztJQUNJLDBCQUFNLEdBQWIsVUFBYyxLQUFjO1FBQ3hCLElBQU0sSUFBSSxHQUFHLEtBQUssS0FBSyxTQUFTLENBQUMsQ0FBQztZQUM5QjtnQkFDSSxVQUFVLEVBQUUsb0JBQW9CO2dCQUNoQyxLQUFLLEVBQUUsS0FBSzthQUNmLENBQUMsQ0FBQyxDQUFDO1lBQ0EsVUFBVSxFQUFFLG9CQUFvQjtTQUNuQyxDQUFDO1FBRU4sTUFBTSxDQUFDLElBQUksQ0FBQyxrQkFBa0IsQ0FBQyxJQUFJLENBQUMsUUFBUSxDQUFDLFFBQVEsQ0FBQyxJQUFJLEVBQUUsSUFBSSxDQUFDLENBQUMsQ0FBQztJQUN2RSxDQUFDO0lBRUQ7O09BRUc7SUFDSSwyQkFBTyxHQUFkLFVBQWUsWUFBb0IsRUFBRSxLQUFjO1FBQy9DLElBQU0sSUFBSSxHQUFHLEtBQUssS0FBSyxTQUFTLENBQUMsQ0FBQztZQUM5QjtnQkFDSSxVQUFVLEVBQUUsZUFBZTtnQkFDM0IsYUFBYSxFQUFFLFlBQVk7Z0JBQzNCLEtBQUssRUFBRSxLQUFLO2FBQ2YsQ0FBQyxDQUFDLENBQUM7WUFDQSxVQUFVLEVBQUUsZUFBZTtZQUMzQixhQUFhLEVBQUUsWUFBWTtTQUM5QixDQUFDO1FBRU4sTUFBTSxDQUFDLElBQUksQ0FBQyxrQkFBa0IsQ0FBQyxJQUFJLENBQUMsUUFBUSxDQUFDLFFBQVEsQ0FBQyxJQUFJLEVBQUUsS0FBSyxDQUFDLENBQUMsQ0FBQztJQUN4RSxDQUFDO0lBRUQ7Ozs7T0FJRztJQUNPLHNDQUFrQixHQUE1QixVQUE2QixlQUFrQztRQUMzRCxNQUFNLENBQUMsZUFBZTthQUVqQixJQUFJLENBQUMsVUFBQSxRQUFRLElBQUksT0FBQSxPQUFPLENBQUMsR0FBRyxDQUFDO1lBQzFCLFFBQVEsQ0FBQyxJQUFJLEVBQUU7WUFDZixPQUFPLENBQUMsT0FBTyxDQUFDLFFBQVEsQ0FBQyxFQUFFLENBQUM7U0FDL0IsQ0FBQyxFQUhnQixDQUdoQixDQUFDO2FBRUYsSUFBSSxDQUFDLFVBQUEsT0FBTztZQUNGLElBQUEsaUJBQUksRUFBRSxpQkFBSSxDQUFZO1lBQzdCLE1BQU0sQ0FBQyxJQUFJLEtBQUssSUFBSSxDQUFDLENBQUM7Z0JBQ2xCLE9BQU8sQ0FBQyxPQUFPLENBQWlCLElBQUksQ0FBQyxDQUFDLENBQUM7Z0JBQ3ZDLE9BQU8sQ0FBQyxNQUFNLENBQUMsSUFBSSxvQkFBVSxDQUF5QixJQUFJLENBQUMsQ0FBQyxDQUFDO1FBQ3JFLENBQUMsQ0FBQzthQUVELEtBQUssQ0FBQyxVQUFDLEtBQVUsSUFBSyxPQUFBLE9BQU8sQ0FBQyxNQUFNLENBQ2pDLEtBQUssQ0FBQyxNQUFNLEtBQUssU0FBUyxDQUFDLENBQUMsQ0FBQyxLQUFLLENBQUMsQ0FBQyxDQUFDLElBQUksU0FBUyxDQUFDLG9CQUFvQixDQUFDLENBQzNFLEVBRnNCLENBRXRCLENBQUMsQ0FBQztJQUNYLENBQUM7SUFDTCxnQkFBQztBQUFELENBQUMsQUF0SEQsSUFzSEMiLCJzb3VyY2VzQ29udGVudCI6WyJpbXBvcnQgQXV0aG9yaXplckludGVyZmFjZSBmcm9tICcuL0NvbnRyYWN0cy9BdXRob3JpemVySW50ZXJmYWNlJztcbmltcG9ydCBUb2tlbkludGVyZmFjZSBmcm9tICcuL0NvbnRyYWN0cy9Ub2tlbkludGVyZmFjZSc7XG5pbXBvcnQgQ2xpZW50UmVxdWVzdHNJbnRlcmZhY2UgZnJvbSAnLi9Db250cmFjdHMvQ2xpZW50UmVxdWVzdHNJbnRlcmZhY2UnO1xuaW1wb3J0IFRva2VuRXJyb3IgZnJvbSAnLi9Ub2tlbkVycm9yJztcbmltcG9ydCBFcnJvclJlc3BvbnNlSW50ZXJmYWNlIGZyb20gJy4vQ29udHJhY3RzL0Vycm9yUmVzcG9uc2VJbnRlcmZhY2UnO1xuXG4vKipcbiAqIEVycm9yIG1lc3NhZ2UuXG4gKi9cbmNvbnN0IFJFU1BPTlNFX0lTX05PVF9KU09OOiBzdHJpbmcgPVxuICAgICdSZXNwb25zZSBpcyBub3QgaW4gSlNPTiBmb3JtYXQuIEl0IG1pZ2h0IGJlIGludmFsaWQgdG9rZW4gVVJMLCBuZXR3b3JrIGVycm9yLCBpbnZhbGlkIHJlc3BvbnNlIGZvcm1hdCBvciBzZXJ2ZXItc2lkZSBlcnJvci4nO1xuXG4vKipcbiAqIEBpbmhlcml0ZG9jXG4gKi9cbmV4cG9ydCBkZWZhdWx0IGNsYXNzIGltcGxlbWVudHMgQXV0aG9yaXplckludGVyZmFjZSB7XG4gICAgLyoqXG4gICAgICogRmV0Y2hlciB3cmFwcGVyLlxuICAgICAqXG4gICAgICogQGludGVybmFsXG4gICAgICovXG4gICAgcHJpdmF0ZSByZWFkb25seSByZXF1ZXN0czogQ2xpZW50UmVxdWVzdHNJbnRlcmZhY2U7XG5cbiAgICAvKipcbiAgICAgKiBDb25zdHJ1Y3Rvci5cbiAgICAgKi9cbiAgICBwdWJsaWMgY29uc3RydWN0b3IocmVxdWVzdHM6IENsaWVudFJlcXVlc3RzSW50ZXJmYWNlKSB7XG4gICAgICAgIHRoaXMucmVxdWVzdHMgPSByZXF1ZXN0cztcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBAaW5oZXJpdGRvY1xuICAgICAqL1xuICAgIGNvZGUoYXV0aENvZGU6IHN0cmluZywgcmVkaXJlY3RVcmk/OiBzdHJpbmcgfCB1bmRlZmluZWQsIGNsaWVudElkPzogc3RyaW5nIHwgdW5kZWZpbmVkKTogUHJvbWlzZTxUb2tlbkludGVyZmFjZT4ge1xuICAgICAgICAvLyBOT1RFOiBpdCBpbXBsZW1lbnRzIHN0ZXBzIDQuMS4zIGFuZCA0LjEuNCBvZiB0aGUgc3BlYy5cbiAgICAgICAgLy8gU3RlcHMgNC4xLjEgYW5kIDQuMS4yIChnZXR0aW5nIGF1dGhvcml6YXRpb24gY29kZSkgYXJlIG91dHNpZGUgb2YgdGhlIGltcGxlbWVudGF0aW9uLlxuICAgICAgICAvLyBUZWNobmljYWxseSB0aG9zZSBzdGVwcyBhcmUgcGxhaW4gcmVkaXJlY3QgdG8gYXV0aG9yaXphdGlvbiBzZXJ2ZXIgKEFTKSBhbmQgcmVhZGluZyB0aGUgY29kZSBmcm9tIFVSTCBpbiBhXG4gICAgICAgIC8vIGNhbGxiYWNrIGZyb20gQVMuIFRoZXkgYXJlIHRyaXZpYWwgYW5kIHZlcnkgc3BlY2lmaWMgdG8gdG9vbHMgdXNlZCBmb3IgYnVpbGRpbmcgdGhlIGFwcC5cbiAgICAgICAgLy9cbiAgICAgICAgLy8gaHR0cHM6Ly90b29scy5pZXRmLm9yZy9odG1sL3JmYzY3NDkjc2VjdGlvbi00LjEuMyBhbmQgaHR0cHM6Ly90b29scy5pZXRmLm9yZy9odG1sL3JmYzY3NDkjc2VjdGlvbi00LjEuNFxuXG4gICAgICAgIGNvbnN0IGFkZEF1dGg6IGJvb2xlYW4gPSAoY2xpZW50SWQgPT09IHVuZGVmaW5lZCk7XG4gICAgICAgIGNvbnN0IGRhdGE6IGFueSA9IGFkZEF1dGggP1xuICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgIGdyYW50X3R5cGU6ICdhdXRob3JpemF0aW9uX2NvZGUnLFxuICAgICAgICAgICAgICAgIGNvZGU6IGF1dGhDb2RlXG4gICAgICAgICAgICB9IDoge1xuICAgICAgICAgICAgICAgIGdyYW50X3R5cGU6ICdhdXRob3JpemF0aW9uX2NvZGUnLFxuICAgICAgICAgICAgICAgIGNvZGU6IGF1dGhDb2RlLFxuICAgICAgICAgICAgICAgIGNsaWVudF9pZDogY2xpZW50SWRcbiAgICAgICAgICAgIH07XG4gICAgICAgIGlmIChyZWRpcmVjdFVyaSAhPT0gdW5kZWZpbmVkKSB7XG4gICAgICAgICAgICBkYXRhLnJlZGlyZWN0X3VyaSA9IHJlZGlyZWN0VXJpO1xuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuIHRoaXMucGFyc2VUb2tlblJlc3BvbnNlKHRoaXMucmVxdWVzdHMuc2VuZEZvcm0oZGF0YSwgYWRkQXV0aCkpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEBpbmhlcml0ZG9jXG4gICAgICovXG4gICAgcHVibGljIHBhc3N3b3JkKHVzZXJOYW1lOiBzdHJpbmcsIHBhc3N3b3JkOiBzdHJpbmcsIHNjb3BlPzogc3RyaW5nKTogUHJvbWlzZTxUb2tlbkludGVyZmFjZT4ge1xuICAgICAgICBjb25zdCBkYXRhID0gc2NvcGUgIT09IHVuZGVmaW5lZCA/XG4gICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgZ3JhbnRfdHlwZTogJ3Bhc3N3b3JkJyxcbiAgICAgICAgICAgICAgICB1c2VybmFtZTogdXNlck5hbWUsXG4gICAgICAgICAgICAgICAgcGFzc3dvcmQ6IHBhc3N3b3JkLFxuICAgICAgICAgICAgICAgIHNjb3BlOiBzY29wZSxcbiAgICAgICAgICAgIH0gOiB7XG4gICAgICAgICAgICAgICAgZ3JhbnRfdHlwZTogJ3Bhc3N3b3JkJyxcbiAgICAgICAgICAgICAgICB1c2VybmFtZTogdXNlck5hbWUsXG4gICAgICAgICAgICAgICAgcGFzc3dvcmQ6IHBhc3N3b3JkLFxuICAgICAgICAgICAgfTtcblxuICAgICAgICByZXR1cm4gdGhpcy5wYXJzZVRva2VuUmVzcG9uc2UodGhpcy5yZXF1ZXN0cy5zZW5kRm9ybShkYXRhLCBmYWxzZSkpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEBpbmhlcml0ZG9jXG4gICAgICovXG4gICAgcHVibGljIGNsaWVudChzY29wZT86IHN0cmluZyk6IFByb21pc2U8VG9rZW5JbnRlcmZhY2U+IHtcbiAgICAgICAgY29uc3QgZGF0YSA9IHNjb3BlICE9PSB1bmRlZmluZWQgP1xuICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgIGdyYW50X3R5cGU6ICdjbGllbnRfY3JlZGVudGlhbHMnLFxuICAgICAgICAgICAgICAgIHNjb3BlOiBzY29wZVxuICAgICAgICAgICAgfSA6IHtcbiAgICAgICAgICAgICAgICBncmFudF90eXBlOiAnY2xpZW50X2NyZWRlbnRpYWxzJ1xuICAgICAgICAgICAgfTtcblxuICAgICAgICByZXR1cm4gdGhpcy5wYXJzZVRva2VuUmVzcG9uc2UodGhpcy5yZXF1ZXN0cy5zZW5kRm9ybShkYXRhLCB0cnVlKSk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQGluaGVyaXRkb2NcbiAgICAgKi9cbiAgICBwdWJsaWMgcmVmcmVzaChyZWZyZXNoVG9rZW46IHN0cmluZywgc2NvcGU/OiBzdHJpbmcpOiBQcm9taXNlPFRva2VuSW50ZXJmYWNlPiB7XG4gICAgICAgIGNvbnN0IGRhdGEgPSBzY29wZSAhPT0gdW5kZWZpbmVkID9cbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICBncmFudF90eXBlOiAncmVmcmVzaF90b2tlbicsXG4gICAgICAgICAgICAgICAgcmVmcmVzaF90b2tlbjogcmVmcmVzaFRva2VuLFxuICAgICAgICAgICAgICAgIHNjb3BlOiBzY29wZSxcbiAgICAgICAgICAgIH0gOiB7XG4gICAgICAgICAgICAgICAgZ3JhbnRfdHlwZTogJ3JlZnJlc2hfdG9rZW4nLFxuICAgICAgICAgICAgICAgIHJlZnJlc2hfdG9rZW46IHJlZnJlc2hUb2tlbixcbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgcmV0dXJuIHRoaXMucGFyc2VUb2tlblJlc3BvbnNlKHRoaXMucmVxdWVzdHMuc2VuZEZvcm0oZGF0YSwgZmFsc2UpKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBDb21tb24gY29kZSBmb3IgcGFyc2luZyB0b2tlbiByZXNwb25zZXMuXG4gICAgICpcbiAgICAgKiBAcGFyYW0gcmVzcG9uc2VQcm9taXNlXG4gICAgICovXG4gICAgcHJvdGVjdGVkIHBhcnNlVG9rZW5SZXNwb25zZShyZXNwb25zZVByb21pc2U6IFByb21pc2U8UmVzcG9uc2U+KTogUHJvbWlzZTxUb2tlbkludGVyZmFjZT4ge1xuICAgICAgICByZXR1cm4gcmVzcG9uc2VQcm9taXNlXG4gICAgICAgICAgICAvLyBoZXJlIHRoZSByZXNwb25zZSBoYXMgb25seSBIVFRQIHN0YXR1cyBidXQgd2Ugd2FudCByZXNvbHZlZCBKU09OIGFzIHdlbGwgc28uLi5cbiAgICAgICAgICAgIC50aGVuKHJlc3BvbnNlID0+IFByb21pc2UuYWxsKFtcbiAgICAgICAgICAgICAgICByZXNwb25zZS5qc29uKCksXG4gICAgICAgICAgICAgICAgUHJvbWlzZS5yZXNvbHZlKHJlc3BvbnNlLm9rKSxcbiAgICAgICAgICAgIF0pKVxuICAgICAgICAgICAgLy8gLi4uIG5vdyB3ZSBoYXZlIGJvdGhcbiAgICAgICAgICAgIC50aGVuKHJlc3VsdHMgPT4ge1xuICAgICAgICAgICAgICAgIGNvbnN0IFtqc29uLCBpc09rXSA9IHJlc3VsdHM7XG4gICAgICAgICAgICAgICAgcmV0dXJuIGlzT2sgPT09IHRydWUgP1xuICAgICAgICAgICAgICAgICAgICBQcm9taXNlLnJlc29sdmUoPFRva2VuSW50ZXJmYWNlPmpzb24pIDpcbiAgICAgICAgICAgICAgICAgICAgUHJvbWlzZS5yZWplY3QobmV3IFRva2VuRXJyb3IoPEVycm9yUmVzcG9uc2VJbnRlcmZhY2U+anNvbikpO1xuICAgICAgICAgICAgfSlcbiAgICAgICAgICAgIC8vIHJldHVybiB0aGUgZXJyb3IgZnJvbSB0aGUgYmxvY2sgYWJvdmUgb3IgcmVwb3J0IHRoZSByZXNwb25zZSB3YXMgbm90IEpTT05cbiAgICAgICAgICAgIC5jYXRjaCgoZXJyb3I6IGFueSkgPT4gUHJvbWlzZS5yZWplY3QoXG4gICAgICAgICAgICAgICAgZXJyb3IucmVhc29uICE9PSB1bmRlZmluZWQgPyBlcnJvciA6IG5ldyBUeXBlRXJyb3IoUkVTUE9OU0VfSVNfTk9UX0pTT04pXG4gICAgICAgICAgICApKTtcbiAgICB9XG59XG4iXX0=