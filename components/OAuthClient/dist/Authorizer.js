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
    function default_1(fetcher) {
        this.fetcher = fetcher;
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
        return this.fetchForm(data);
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
        return this.fetchForm(data);
    };
    /**
     * Fetch form to token endpoint.
     *
     * @internal
     */
    default_1.prototype.fetchForm = function (data) {
        return this.fetcher.sendForm(data)
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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiQXV0aG9yaXplci5qcyIsInNvdXJjZVJvb3QiOiIvaG9tZS9uZW9tZXJ4L1Byb2plY3RzL2xpbW9uY2VsbG8vZnJhbWV3b3JrL2NvbXBvbmVudHMvT0F1dGhDbGllbnQvZGlzdC8iLCJzb3VyY2VzIjpbIkF1dGhvcml6ZXIudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7QUFHQSwyQ0FBc0M7QUFHdEM7O0dBRUc7QUFDSCxJQUFNLG9CQUFvQixHQUN0Qiw2SEFBNkgsQ0FBQztBQUVsSTs7R0FFRztBQUNIO0lBUUk7O09BRUc7SUFDSCxtQkFBbUIsT0FBZ0M7UUFDL0MsSUFBSSxDQUFDLE9BQU8sR0FBRyxPQUFPLENBQUM7SUFDM0IsQ0FBQztJQUVEOztPQUVHO0lBQ0ksNEJBQVEsR0FBZixVQUFnQixRQUFnQixFQUFFLFFBQWdCLEVBQUUsS0FBYztRQUM5RCxJQUFNLElBQUksR0FBRyxLQUFLLEtBQUssU0FBUyxDQUFDLENBQUM7WUFDOUI7Z0JBQ0ksWUFBWSxFQUFFLFVBQVU7Z0JBQ3hCLFVBQVUsRUFBRSxRQUFRO2dCQUNwQixVQUFVLEVBQUUsUUFBUTtnQkFDcEIsT0FBTyxFQUFFLEtBQUs7YUFDakIsQ0FBQyxDQUFDLENBQUM7WUFDQSxZQUFZLEVBQUUsVUFBVTtZQUN4QixVQUFVLEVBQUUsUUFBUTtZQUNwQixVQUFVLEVBQUUsUUFBUTtTQUN2QixDQUFDO1FBRU4sTUFBTSxDQUFDLElBQUksQ0FBQyxTQUFTLENBQUMsSUFBSSxDQUFDLENBQUM7SUFDaEMsQ0FBQztJQUVEOztPQUVHO0lBQ0ksMkJBQU8sR0FBZCxVQUFlLFlBQW9CLEVBQUUsS0FBYztRQUMvQyxJQUFNLElBQUksR0FBRyxLQUFLLEtBQUssU0FBUyxDQUFDLENBQUM7WUFDOUI7Z0JBQ0ksWUFBWSxFQUFFLGVBQWU7Z0JBQzdCLGVBQWUsRUFBRSxZQUFZO2dCQUM3QixPQUFPLEVBQUUsS0FBSzthQUNqQixDQUFDLENBQUMsQ0FBQztZQUNBLFlBQVksRUFBRSxlQUFlO1lBQzdCLGVBQWUsRUFBRSxZQUFZO1NBQ2hDLENBQUM7UUFFTixNQUFNLENBQUMsSUFBSSxDQUFDLFNBQVMsQ0FBQyxJQUFJLENBQUMsQ0FBQztJQUNoQyxDQUFDO0lBRUQ7Ozs7T0FJRztJQUNLLDZCQUFTLEdBQWpCLFVBQWtCLElBQVk7UUFDMUIsTUFBTSxDQUFDLElBQUksQ0FBQyxPQUFPLENBQUMsUUFBUSxDQUFDLElBQUksQ0FBQzthQUU3QixJQUFJLENBQUMsVUFBQSxRQUFRLElBQUksT0FBQSxPQUFPLENBQUMsR0FBRyxDQUFDO1lBQzFCLFFBQVEsQ0FBQyxJQUFJLEVBQUU7WUFDZixPQUFPLENBQUMsT0FBTyxDQUFDLFFBQVEsQ0FBQyxFQUFFLENBQUM7U0FDL0IsQ0FBQyxFQUhnQixDQUdoQixDQUFDO2FBRUYsSUFBSSxDQUFDLFVBQUEsT0FBTztZQUNGLElBQUEsaUJBQUksRUFBRSxpQkFBSSxDQUFZO1lBQzdCLEVBQUUsQ0FBQyxDQUFDLElBQUksS0FBSyxLQUFLLENBQUMsQ0FBQyxDQUFDO2dCQUNqQixNQUFNLElBQUksb0JBQVUsQ0FBeUIsSUFBSSxDQUFDLENBQUM7WUFDdkQsQ0FBQztZQUVELE1BQU0sQ0FBQyxPQUFPLENBQUMsT0FBTyxDQUFpQixJQUFJLENBQUMsQ0FBQztRQUNqRCxDQUFDLENBQUM7YUFDRCxLQUFLLENBQUMsVUFBQyxLQUFVO1lBQ2QseUNBQXlDO1lBQ3pDLEVBQUUsQ0FBQyxDQUFDLEtBQUssQ0FBQyxNQUFNLEtBQUssU0FBUyxDQUFDLENBQUMsQ0FBQztnQkFDN0IsTUFBTSxLQUFLLENBQUM7WUFDaEIsQ0FBQztZQUVELDJDQUEyQztZQUMzQyxNQUFNLElBQUksU0FBUyxDQUFDLG9CQUFvQixDQUFDLENBQUM7UUFDOUMsQ0FBQyxDQUFDLENBQUM7SUFDWCxDQUFDO0lBQ0wsZ0JBQUM7QUFBRCxDQUFDLEFBbEZELElBa0ZDIiwic291cmNlc0NvbnRlbnQiOlsiaW1wb3J0IEF1dGhvcml6ZXJJbnRlcmZhY2UgZnJvbSAnLi9Db250cmFjdHMvQXV0aG9yaXplckludGVyZmFjZSc7XG5pbXBvcnQgVG9rZW5JbnRlcmZhY2UgZnJvbSAnLi9Db250cmFjdHMvVG9rZW5JbnRlcmZhY2UnO1xuaW1wb3J0IENsaWVudFJlcXVlc3RzSW50ZXJmYWNlIGZyb20gJy4vQ29udHJhY3RzL0NsaWVudFJlcXVlc3RzSW50ZXJmYWNlJztcbmltcG9ydCBUb2tlbkVycm9yIGZyb20gJy4vVG9rZW5FcnJvcic7XG5pbXBvcnQgRXJyb3JSZXNwb25zZUludGVyZmFjZSBmcm9tICcuL0NvbnRyYWN0cy9FcnJvclJlc3BvbnNlSW50ZXJmYWNlJztcblxuLyoqXG4gKiBFcnJvciBtZXNzYWdlLlxuICovXG5jb25zdCBSRVNQT05TRV9JU19OT1RfSlNPTjogc3RyaW5nID1cbiAgICAnUmVzcG9uc2UgaXMgbm90IGluIEpTT04gZm9ybWF0LiBJdCBtaWdodCBiZSBpbnZhbGlkIHRva2VuIFVSTCwgbmV0d29yayBlcnJvciwgaW52YWxpZCByZXNwb25zZSBmb3JtYXQgb3Igc2VydmVyLXNpZGUgZXJyb3IuJztcblxuLyoqXG4gKiBAaW5oZXJpdGRvY1xuICovXG5leHBvcnQgZGVmYXVsdCBjbGFzcyBpbXBsZW1lbnRzIEF1dGhvcml6ZXJJbnRlcmZhY2Uge1xuICAgIC8qKlxuICAgICAqIEZldGNoZXIgd3JhcHBlci5cbiAgICAgKlxuICAgICAqIEBpbnRlcm5hbFxuICAgICAqL1xuICAgIHByaXZhdGUgcmVhZG9ubHkgZmV0Y2hlcjogQ2xpZW50UmVxdWVzdHNJbnRlcmZhY2U7XG5cbiAgICAvKipcbiAgICAgKiBDb25zdHJ1Y3Rvci5cbiAgICAgKi9cbiAgICBwdWJsaWMgY29uc3RydWN0b3IoZmV0Y2hlcjogQ2xpZW50UmVxdWVzdHNJbnRlcmZhY2UpIHtcbiAgICAgICAgdGhpcy5mZXRjaGVyID0gZmV0Y2hlcjtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBAaW5oZXJpdGRvY1xuICAgICAqL1xuICAgIHB1YmxpYyBwYXNzd29yZCh1c2VyTmFtZTogc3RyaW5nLCBwYXNzd29yZDogc3RyaW5nLCBzY29wZT86IHN0cmluZyk6IFByb21pc2U8VG9rZW5JbnRlcmZhY2U+IHtcbiAgICAgICAgY29uc3QgZGF0YSA9IHNjb3BlICE9PSB1bmRlZmluZWQgP1xuICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICdncmFudF90eXBlJzogJ3Bhc3N3b3JkJyxcbiAgICAgICAgICAgICAgICAndXNlcm5hbWUnOiB1c2VyTmFtZSxcbiAgICAgICAgICAgICAgICAncGFzc3dvcmQnOiBwYXNzd29yZCxcbiAgICAgICAgICAgICAgICAnc2NvcGUnOiBzY29wZSxcbiAgICAgICAgICAgIH0gOiB7XG4gICAgICAgICAgICAgICAgJ2dyYW50X3R5cGUnOiAncGFzc3dvcmQnLFxuICAgICAgICAgICAgICAgICd1c2VybmFtZSc6IHVzZXJOYW1lLFxuICAgICAgICAgICAgICAgICdwYXNzd29yZCc6IHBhc3N3b3JkLFxuICAgICAgICAgICAgfTtcblxuICAgICAgICByZXR1cm4gdGhpcy5mZXRjaEZvcm0oZGF0YSk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQGluaGVyaXRkb2NcbiAgICAgKi9cbiAgICBwdWJsaWMgcmVmcmVzaChyZWZyZXNoVG9rZW46IHN0cmluZywgc2NvcGU/OiBzdHJpbmcpOiBQcm9taXNlPFRva2VuSW50ZXJmYWNlPiB7XG4gICAgICAgIGNvbnN0IGRhdGEgPSBzY29wZSAhPT0gdW5kZWZpbmVkID9cbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICAnZ3JhbnRfdHlwZSc6ICdyZWZyZXNoX3Rva2VuJyxcbiAgICAgICAgICAgICAgICAncmVmcmVzaF90b2tlbic6IHJlZnJlc2hUb2tlbixcbiAgICAgICAgICAgICAgICAnc2NvcGUnOiBzY29wZSxcbiAgICAgICAgICAgIH0gOiB7XG4gICAgICAgICAgICAgICAgJ2dyYW50X3R5cGUnOiAncmVmcmVzaF90b2tlbicsXG4gICAgICAgICAgICAgICAgJ3JlZnJlc2hfdG9rZW4nOiByZWZyZXNoVG9rZW4sXG4gICAgICAgICAgICB9O1xuXG4gICAgICAgIHJldHVybiB0aGlzLmZldGNoRm9ybShkYXRhKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBGZXRjaCBmb3JtIHRvIHRva2VuIGVuZHBvaW50LlxuICAgICAqXG4gICAgICogQGludGVybmFsXG4gICAgICovXG4gICAgcHJpdmF0ZSBmZXRjaEZvcm0oZGF0YTogb2JqZWN0KTogUHJvbWlzZTxUb2tlbkludGVyZmFjZT4ge1xuICAgICAgICByZXR1cm4gdGhpcy5mZXRjaGVyLnNlbmRGb3JtKGRhdGEpXG4gICAgICAgICAgICAvLyBoZXJlIHRoZSByZXNwb25zZSBoYXMgb25seSBIVFRQIHN0YXR1cyBidXQgd2Ugd2FudCByZXNvbHZlZCBKU09OIGFzIHdlbGwgc28uLi5cbiAgICAgICAgICAgIC50aGVuKHJlc3BvbnNlID0+IFByb21pc2UuYWxsKFtcbiAgICAgICAgICAgICAgICByZXNwb25zZS5qc29uKCksXG4gICAgICAgICAgICAgICAgUHJvbWlzZS5yZXNvbHZlKHJlc3BvbnNlLm9rKSxcbiAgICAgICAgICAgIF0pKVxuICAgICAgICAgICAgLy8gLi4uIG5vdyB3ZSBoYXZlIGJvdGhcbiAgICAgICAgICAgIC50aGVuKHJlc3VsdHMgPT4ge1xuICAgICAgICAgICAgICAgIGNvbnN0IFtqc29uLCBpc09rXSA9IHJlc3VsdHM7XG4gICAgICAgICAgICAgICAgaWYgKGlzT2sgPT09IGZhbHNlKSB7XG4gICAgICAgICAgICAgICAgICAgIHRocm93IG5ldyBUb2tlbkVycm9yKDxFcnJvclJlc3BvbnNlSW50ZXJmYWNlPmpzb24pO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIHJldHVybiBQcm9taXNlLnJlc29sdmUoPFRva2VuSW50ZXJmYWNlPmpzb24pO1xuICAgICAgICAgICAgfSlcbiAgICAgICAgICAgIC5jYXRjaCgoZXJyb3I6IGFueSkgPT4ge1xuICAgICAgICAgICAgICAgIC8vIHJldGhyb3cgdGhlIGVycm9yIGZyb20gdGhlIGJsb2NrIGFib3ZlXG4gICAgICAgICAgICAgICAgaWYgKGVycm9yLnJlYXNvbiAhPT0gdW5kZWZpbmVkKSB7XG4gICAgICAgICAgICAgICAgICAgIHRocm93IGVycm9yO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIC8vIGlmIHdlIGFyZSBoZXJlIHRoZSByZXNwb25zZSB3YXMgbm90IEpTT05cbiAgICAgICAgICAgICAgICB0aHJvdyBuZXcgVHlwZUVycm9yKFJFU1BPTlNFX0lTX05PVF9KU09OKTtcbiAgICAgICAgICAgIH0pO1xuICAgIH1cbn1cbiJdfQ==