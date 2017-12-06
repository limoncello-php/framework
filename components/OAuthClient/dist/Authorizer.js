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
    default_1.prototype.password = function (userName, password, scope) {
        var data = scope !== undefined ?
            {
                'grant_type': 'password',
                'username': userName,
                'password': password,
                'scope': scope,
            } : {
            'grant_type': 'password',
            'username': userName,
            'password': password,
        };
        return this.parseTokenResponse(this.requests.sendForm(data, false));
    };
    /**
     * @inheritdoc
     */
    default_1.prototype.client = function (scope) {
        var data = scope !== undefined ?
            {
                'grant_type': 'client_credentials',
                'scope': scope
            } : {
            'grant_type': 'client_credentials'
        };
        return this.parseTokenResponse(this.requests.sendForm(data, true));
    };
    /**
     * @inheritdoc
     */
    default_1.prototype.refresh = function (refreshToken, scope) {
        var data = scope !== undefined ?
            {
                'grant_type': 'refresh_token',
                'refresh_token': refreshToken,
                'scope': scope,
            } : {
            'grant_type': 'refresh_token',
            'refresh_token': refreshToken,
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
            if (isOk === false) {
                throw new TokenError_1.default(json);
            }
            return Promise.resolve(json);
        })
            .catch(function (error) {
            // rethrow the error from the block above
            if (error.reason !== undefined) {
                throw error;
            }
            // if we are here the response was not JSON
            throw new TypeError(RESPONSE_IS_NOT_JSON);
        });
    };
    return default_1;
}());
exports.default = default_1;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiQXV0aG9yaXplci5qcyIsInNvdXJjZVJvb3QiOiIvaG9tZS9uZW9tZXJ4L1Byb2plY3RzL2xpbW9uY2VsbG8vZnJhbWV3b3JrL2NvbXBvbmVudHMvT0F1dGhDbGllbnQvZGlzdC8iLCJzb3VyY2VzIjpbIkF1dGhvcml6ZXIudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7QUFHQSwyQ0FBc0M7QUFHdEM7O0dBRUc7QUFDSCxJQUFNLG9CQUFvQixHQUN0Qiw2SEFBNkgsQ0FBQztBQUVsSTs7R0FFRztBQUNIO0lBUUk7O09BRUc7SUFDSCxtQkFBbUIsUUFBaUM7UUFDaEQsSUFBSSxDQUFDLFFBQVEsR0FBRyxRQUFRLENBQUM7SUFDN0IsQ0FBQztJQUVEOztPQUVHO0lBQ0ksNEJBQVEsR0FBZixVQUFnQixRQUFnQixFQUFFLFFBQWdCLEVBQUUsS0FBYztRQUM5RCxJQUFNLElBQUksR0FBRyxLQUFLLEtBQUssU0FBUyxDQUFDLENBQUM7WUFDOUI7Z0JBQ0ksWUFBWSxFQUFFLFVBQVU7Z0JBQ3hCLFVBQVUsRUFBRSxRQUFRO2dCQUNwQixVQUFVLEVBQUUsUUFBUTtnQkFDcEIsT0FBTyxFQUFFLEtBQUs7YUFDakIsQ0FBQyxDQUFDLENBQUM7WUFDQSxZQUFZLEVBQUUsVUFBVTtZQUN4QixVQUFVLEVBQUUsUUFBUTtZQUNwQixVQUFVLEVBQUUsUUFBUTtTQUN2QixDQUFDO1FBRU4sTUFBTSxDQUFDLElBQUksQ0FBQyxrQkFBa0IsQ0FBQyxJQUFJLENBQUMsUUFBUSxDQUFDLFFBQVEsQ0FBQyxJQUFJLEVBQUUsS0FBSyxDQUFDLENBQUMsQ0FBQztJQUN4RSxDQUFDO0lBRUQ7O09BRUc7SUFDSSwwQkFBTSxHQUFiLFVBQWMsS0FBYztRQUN4QixJQUFNLElBQUksR0FBRyxLQUFLLEtBQUssU0FBUyxDQUFDLENBQUM7WUFDOUI7Z0JBQ0ksWUFBWSxFQUFFLG9CQUFvQjtnQkFDbEMsT0FBTyxFQUFFLEtBQUs7YUFDakIsQ0FBQyxDQUFDLENBQUM7WUFDQSxZQUFZLEVBQUUsb0JBQW9CO1NBQ3JDLENBQUM7UUFFTixNQUFNLENBQUMsSUFBSSxDQUFDLGtCQUFrQixDQUFDLElBQUksQ0FBQyxRQUFRLENBQUMsUUFBUSxDQUFDLElBQUksRUFBRSxJQUFJLENBQUMsQ0FBQyxDQUFDO0lBQ3ZFLENBQUM7SUFFRDs7T0FFRztJQUNJLDJCQUFPLEdBQWQsVUFBZSxZQUFvQixFQUFFLEtBQWM7UUFDL0MsSUFBTSxJQUFJLEdBQUcsS0FBSyxLQUFLLFNBQVMsQ0FBQyxDQUFDO1lBQzlCO2dCQUNJLFlBQVksRUFBRSxlQUFlO2dCQUM3QixlQUFlLEVBQUUsWUFBWTtnQkFDN0IsT0FBTyxFQUFFLEtBQUs7YUFDakIsQ0FBQyxDQUFDLENBQUM7WUFDQSxZQUFZLEVBQUUsZUFBZTtZQUM3QixlQUFlLEVBQUUsWUFBWTtTQUNoQyxDQUFDO1FBRU4sTUFBTSxDQUFDLElBQUksQ0FBQyxrQkFBa0IsQ0FBQyxJQUFJLENBQUMsUUFBUSxDQUFDLFFBQVEsQ0FBQyxJQUFJLEVBQUUsS0FBSyxDQUFDLENBQUMsQ0FBQztJQUN4RSxDQUFDO0lBRUQ7Ozs7T0FJRztJQUNPLHNDQUFrQixHQUE1QixVQUE2QixlQUFrQztRQUMzRCxNQUFNLENBQUMsZUFBZTthQUVqQixJQUFJLENBQUMsVUFBQSxRQUFRLElBQUksT0FBQSxPQUFPLENBQUMsR0FBRyxDQUFDO1lBQzFCLFFBQVEsQ0FBQyxJQUFJLEVBQUU7WUFDZixPQUFPLENBQUMsT0FBTyxDQUFDLFFBQVEsQ0FBQyxFQUFFLENBQUM7U0FDL0IsQ0FBQyxFQUhnQixDQUdoQixDQUFDO2FBRUYsSUFBSSxDQUFDLFVBQUEsT0FBTztZQUNGLElBQUEsaUJBQUksRUFBRSxpQkFBSSxDQUFZO1lBQzdCLEVBQUUsQ0FBQyxDQUFDLElBQUksS0FBSyxLQUFLLENBQUMsQ0FBQyxDQUFDO2dCQUNqQixNQUFNLElBQUksb0JBQVUsQ0FBeUIsSUFBSSxDQUFDLENBQUM7WUFDdkQsQ0FBQztZQUVELE1BQU0sQ0FBQyxPQUFPLENBQUMsT0FBTyxDQUFpQixJQUFJLENBQUMsQ0FBQztRQUNqRCxDQUFDLENBQUM7YUFDRCxLQUFLLENBQUMsVUFBQyxLQUFVO1lBQ2QseUNBQXlDO1lBQ3pDLEVBQUUsQ0FBQyxDQUFDLEtBQUssQ0FBQyxNQUFNLEtBQUssU0FBUyxDQUFDLENBQUMsQ0FBQztnQkFDN0IsTUFBTSxLQUFLLENBQUM7WUFDaEIsQ0FBQztZQUVELDJDQUEyQztZQUMzQyxNQUFNLElBQUksU0FBUyxDQUFDLG9CQUFvQixDQUFDLENBQUM7UUFDOUMsQ0FBQyxDQUFDLENBQUM7SUFDWCxDQUFDO0lBQ0wsZ0JBQUM7QUFBRCxDQUFDLEFBakdELElBaUdDIiwic291cmNlc0NvbnRlbnQiOlsiaW1wb3J0IEF1dGhvcml6ZXJJbnRlcmZhY2UgZnJvbSAnLi9Db250cmFjdHMvQXV0aG9yaXplckludGVyZmFjZSc7XG5pbXBvcnQgVG9rZW5JbnRlcmZhY2UgZnJvbSAnLi9Db250cmFjdHMvVG9rZW5JbnRlcmZhY2UnO1xuaW1wb3J0IENsaWVudFJlcXVlc3RzSW50ZXJmYWNlIGZyb20gJy4vQ29udHJhY3RzL0NsaWVudFJlcXVlc3RzSW50ZXJmYWNlJztcbmltcG9ydCBUb2tlbkVycm9yIGZyb20gJy4vVG9rZW5FcnJvcic7XG5pbXBvcnQgRXJyb3JSZXNwb25zZUludGVyZmFjZSBmcm9tICcuL0NvbnRyYWN0cy9FcnJvclJlc3BvbnNlSW50ZXJmYWNlJztcblxuLyoqXG4gKiBFcnJvciBtZXNzYWdlLlxuICovXG5jb25zdCBSRVNQT05TRV9JU19OT1RfSlNPTjogc3RyaW5nID1cbiAgICAnUmVzcG9uc2UgaXMgbm90IGluIEpTT04gZm9ybWF0LiBJdCBtaWdodCBiZSBpbnZhbGlkIHRva2VuIFVSTCwgbmV0d29yayBlcnJvciwgaW52YWxpZCByZXNwb25zZSBmb3JtYXQgb3Igc2VydmVyLXNpZGUgZXJyb3IuJztcblxuLyoqXG4gKiBAaW5oZXJpdGRvY1xuICovXG5leHBvcnQgZGVmYXVsdCBjbGFzcyBpbXBsZW1lbnRzIEF1dGhvcml6ZXJJbnRlcmZhY2Uge1xuICAgIC8qKlxuICAgICAqIEZldGNoZXIgd3JhcHBlci5cbiAgICAgKlxuICAgICAqIEBpbnRlcm5hbFxuICAgICAqL1xuICAgIHByaXZhdGUgcmVhZG9ubHkgcmVxdWVzdHM6IENsaWVudFJlcXVlc3RzSW50ZXJmYWNlO1xuXG4gICAgLyoqXG4gICAgICogQ29uc3RydWN0b3IuXG4gICAgICovXG4gICAgcHVibGljIGNvbnN0cnVjdG9yKHJlcXVlc3RzOiBDbGllbnRSZXF1ZXN0c0ludGVyZmFjZSkge1xuICAgICAgICB0aGlzLnJlcXVlc3RzID0gcmVxdWVzdHM7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQGluaGVyaXRkb2NcbiAgICAgKi9cbiAgICBwdWJsaWMgcGFzc3dvcmQodXNlck5hbWU6IHN0cmluZywgcGFzc3dvcmQ6IHN0cmluZywgc2NvcGU/OiBzdHJpbmcpOiBQcm9taXNlPFRva2VuSW50ZXJmYWNlPiB7XG4gICAgICAgIGNvbnN0IGRhdGEgPSBzY29wZSAhPT0gdW5kZWZpbmVkID9cbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICAnZ3JhbnRfdHlwZSc6ICdwYXNzd29yZCcsXG4gICAgICAgICAgICAgICAgJ3VzZXJuYW1lJzogdXNlck5hbWUsXG4gICAgICAgICAgICAgICAgJ3Bhc3N3b3JkJzogcGFzc3dvcmQsXG4gICAgICAgICAgICAgICAgJ3Njb3BlJzogc2NvcGUsXG4gICAgICAgICAgICB9IDoge1xuICAgICAgICAgICAgICAgICdncmFudF90eXBlJzogJ3Bhc3N3b3JkJyxcbiAgICAgICAgICAgICAgICAndXNlcm5hbWUnOiB1c2VyTmFtZSxcbiAgICAgICAgICAgICAgICAncGFzc3dvcmQnOiBwYXNzd29yZCxcbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgcmV0dXJuIHRoaXMucGFyc2VUb2tlblJlc3BvbnNlKHRoaXMucmVxdWVzdHMuc2VuZEZvcm0oZGF0YSwgZmFsc2UpKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBAaW5oZXJpdGRvY1xuICAgICAqL1xuICAgIHB1YmxpYyBjbGllbnQoc2NvcGU/OiBzdHJpbmcpOiBQcm9taXNlPFRva2VuSW50ZXJmYWNlPiB7XG4gICAgICAgIGNvbnN0IGRhdGEgPSBzY29wZSAhPT0gdW5kZWZpbmVkID9cbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICAnZ3JhbnRfdHlwZSc6ICdjbGllbnRfY3JlZGVudGlhbHMnLFxuICAgICAgICAgICAgICAgICdzY29wZSc6IHNjb3BlXG4gICAgICAgICAgICB9IDoge1xuICAgICAgICAgICAgICAgICdncmFudF90eXBlJzogJ2NsaWVudF9jcmVkZW50aWFscydcbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgcmV0dXJuIHRoaXMucGFyc2VUb2tlblJlc3BvbnNlKHRoaXMucmVxdWVzdHMuc2VuZEZvcm0oZGF0YSwgdHJ1ZSkpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEBpbmhlcml0ZG9jXG4gICAgICovXG4gICAgcHVibGljIHJlZnJlc2gocmVmcmVzaFRva2VuOiBzdHJpbmcsIHNjb3BlPzogc3RyaW5nKTogUHJvbWlzZTxUb2tlbkludGVyZmFjZT4ge1xuICAgICAgICBjb25zdCBkYXRhID0gc2NvcGUgIT09IHVuZGVmaW5lZCA/XG4gICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgJ2dyYW50X3R5cGUnOiAncmVmcmVzaF90b2tlbicsXG4gICAgICAgICAgICAgICAgJ3JlZnJlc2hfdG9rZW4nOiByZWZyZXNoVG9rZW4sXG4gICAgICAgICAgICAgICAgJ3Njb3BlJzogc2NvcGUsXG4gICAgICAgICAgICB9IDoge1xuICAgICAgICAgICAgICAgICdncmFudF90eXBlJzogJ3JlZnJlc2hfdG9rZW4nLFxuICAgICAgICAgICAgICAgICdyZWZyZXNoX3Rva2VuJzogcmVmcmVzaFRva2VuLFxuICAgICAgICAgICAgfTtcblxuICAgICAgICByZXR1cm4gdGhpcy5wYXJzZVRva2VuUmVzcG9uc2UodGhpcy5yZXF1ZXN0cy5zZW5kRm9ybShkYXRhLCBmYWxzZSkpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIENvbW1vbiBjb2RlIGZvciBwYXJzaW5nIHRva2VuIHJlc3BvbnNlcy5cbiAgICAgKlxuICAgICAqIEBwYXJhbSByZXNwb25zZVByb21pc2VcbiAgICAgKi9cbiAgICBwcm90ZWN0ZWQgcGFyc2VUb2tlblJlc3BvbnNlKHJlc3BvbnNlUHJvbWlzZTogUHJvbWlzZTxSZXNwb25zZT4pOiBQcm9taXNlPFRva2VuSW50ZXJmYWNlPiB7XG4gICAgICAgIHJldHVybiByZXNwb25zZVByb21pc2VcbiAgICAgICAgICAgIC8vIGhlcmUgdGhlIHJlc3BvbnNlIGhhcyBvbmx5IEhUVFAgc3RhdHVzIGJ1dCB3ZSB3YW50IHJlc29sdmVkIEpTT04gYXMgd2VsbCBzby4uLlxuICAgICAgICAgICAgLnRoZW4ocmVzcG9uc2UgPT4gUHJvbWlzZS5hbGwoW1xuICAgICAgICAgICAgICAgIHJlc3BvbnNlLmpzb24oKSxcbiAgICAgICAgICAgICAgICBQcm9taXNlLnJlc29sdmUocmVzcG9uc2Uub2spLFxuICAgICAgICAgICAgXSkpXG4gICAgICAgICAgICAvLyAuLi4gbm93IHdlIGhhdmUgYm90aFxuICAgICAgICAgICAgLnRoZW4ocmVzdWx0cyA9PiB7XG4gICAgICAgICAgICAgICAgY29uc3QgW2pzb24sIGlzT2tdID0gcmVzdWx0cztcbiAgICAgICAgICAgICAgICBpZiAoaXNPayA9PT0gZmFsc2UpIHtcbiAgICAgICAgICAgICAgICAgICAgdGhyb3cgbmV3IFRva2VuRXJyb3IoPEVycm9yUmVzcG9uc2VJbnRlcmZhY2U+anNvbik7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgcmV0dXJuIFByb21pc2UucmVzb2x2ZSg8VG9rZW5JbnRlcmZhY2U+anNvbik7XG4gICAgICAgICAgICB9KVxuICAgICAgICAgICAgLmNhdGNoKChlcnJvcjogYW55KSA9PiB7XG4gICAgICAgICAgICAgICAgLy8gcmV0aHJvdyB0aGUgZXJyb3IgZnJvbSB0aGUgYmxvY2sgYWJvdmVcbiAgICAgICAgICAgICAgICBpZiAoZXJyb3IucmVhc29uICE9PSB1bmRlZmluZWQpIHtcbiAgICAgICAgICAgICAgICAgICAgdGhyb3cgZXJyb3I7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgLy8gaWYgd2UgYXJlIGhlcmUgdGhlIHJlc3BvbnNlIHdhcyBub3QgSlNPTlxuICAgICAgICAgICAgICAgIHRocm93IG5ldyBUeXBlRXJyb3IoUkVTUE9OU0VfSVNfTk9UX0pTT04pO1xuICAgICAgICAgICAgfSk7XG4gICAgfVxufVxuIl19