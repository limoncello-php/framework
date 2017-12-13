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
var __extends = (this && this.__extends) || (function () {
    var extendStatics = Object.setPrototypeOf ||
        ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
        function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * OAuth token error.
 */
var TokenError = /** @class */ (function (_super) {
    __extends(TokenError, _super);
    /**
     * Constructor.
     */
    function TokenError(reason) {
        var args = [];
        for (var _i = 1; _i < arguments.length; _i++) {
            args[_i - 1] = arguments[_i];
        }
        var _this = _super.apply(this, args) || this;
        _this.reason = reason;
        if (reason.error_description !== null && reason.error_description !== undefined) {
            _this.message = reason.error_description;
        }
        return _this;
    }
    return TokenError;
}(Error));
exports.TokenError = TokenError;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiVG9rZW5FcnJvci5qcyIsInNvdXJjZVJvb3QiOiIvaG9tZS9uZW9tZXJ4L1Byb2plY3RzL2xpbW9uY2VsbG8vZnJhbWV3b3JrL2NvbXBvbmVudHMvT0F1dGhDbGllbnQvZGlzdC8iLCJzb3VyY2VzIjpbIk9BdXRoQ2xpZW50L1Rva2VuRXJyb3IudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IjtBQUFBOzs7Ozs7Ozs7Ozs7OztHQWNHOzs7Ozs7Ozs7Ozs7QUFJSDs7R0FFRztBQUNIO0lBQWdDLDhCQUFLO0lBTWpDOztPQUVHO0lBQ0gsb0JBQW1CLE1BQThCO1FBQUUsY0FBYzthQUFkLFVBQWMsRUFBZCxxQkFBYyxFQUFkLElBQWM7WUFBZCw2QkFBYzs7UUFBakUsK0JBQ2EsSUFBSSxVQU1oQjtRQUpHLEtBQUksQ0FBQyxNQUFNLEdBQUcsTUFBTSxDQUFDO1FBQ3JCLEVBQUUsQ0FBQyxDQUFDLE1BQU0sQ0FBQyxpQkFBaUIsS0FBSyxJQUFJLElBQUksTUFBTSxDQUFDLGlCQUFpQixLQUFLLFNBQVMsQ0FBQyxDQUFDLENBQUM7WUFDOUUsS0FBSSxDQUFDLE9BQU8sR0FBRyxNQUFNLENBQUMsaUJBQWlCLENBQUM7UUFDNUMsQ0FBQzs7SUFDTCxDQUFDO0lBQ0wsaUJBQUM7QUFBRCxDQUFDLEFBakJELENBQWdDLEtBQUssR0FpQnBDO0FBakJZLGdDQUFVIiwic291cmNlc0NvbnRlbnQiOlsiLyoqXG4gKiBDb3B5cmlnaHQgMjAxNS0yMDE3IGluZm9AbmVvbWVyeC5jb21cbiAqXG4gKiBMaWNlbnNlZCB1bmRlciB0aGUgQXBhY2hlIExpY2Vuc2UsIFZlcnNpb24gMi4wICh0aGUgXCJMaWNlbnNlXCIpO1xuICogeW91IG1heSBub3QgdXNlIHRoaXMgZmlsZSBleGNlcHQgaW4gY29tcGxpYW5jZSB3aXRoIHRoZSBMaWNlbnNlLlxuICogWW91IG1heSBvYnRhaW4gYSBjb3B5IG9mIHRoZSBMaWNlbnNlIGF0XG4gKlxuICogaHR0cDovL3d3dy5hcGFjaGUub3JnL2xpY2Vuc2VzL0xJQ0VOU0UtMi4wXG4gKlxuICogVW5sZXNzIHJlcXVpcmVkIGJ5IGFwcGxpY2FibGUgbGF3IG9yIGFncmVlZCB0byBpbiB3cml0aW5nLCBzb2Z0d2FyZVxuICogZGlzdHJpYnV0ZWQgdW5kZXIgdGhlIExpY2Vuc2UgaXMgZGlzdHJpYnV0ZWQgb24gYW4gXCJBUyBJU1wiIEJBU0lTLFxuICogV0lUSE9VVCBXQVJSQU5USUVTIE9SIENPTkRJVElPTlMgT0YgQU5ZIEtJTkQsIGVpdGhlciBleHByZXNzIG9yIGltcGxpZWQuXG4gKiBTZWUgdGhlIExpY2Vuc2UgZm9yIHRoZSBzcGVjaWZpYyBsYW5ndWFnZSBnb3Zlcm5pbmcgcGVybWlzc2lvbnMgYW5kXG4gKiBsaW1pdGF0aW9ucyB1bmRlciB0aGUgTGljZW5zZS5cbiAqL1xuXG5pbXBvcnQgeyBFcnJvclJlc3BvbnNlSW50ZXJmYWNlIH0gZnJvbSAnLi8uLi9Db250cmFjdHMvRXJyb3JSZXNwb25zZUludGVyZmFjZSc7XG5cbi8qKlxuICogT0F1dGggdG9rZW4gZXJyb3IuXG4gKi9cbmV4cG9ydCBjbGFzcyBUb2tlbkVycm9yIGV4dGVuZHMgRXJyb3Ige1xuICAgIC8qKlxuICAgICAqIE9BdXRoIEVycm9yIGRldGFpbHMuXG4gICAgICovXG4gICAgcHVibGljIHJlYWRvbmx5IHJlYXNvbjogRXJyb3JSZXNwb25zZUludGVyZmFjZTtcblxuICAgIC8qKlxuICAgICAqIENvbnN0cnVjdG9yLlxuICAgICAqL1xuICAgIHB1YmxpYyBjb25zdHJ1Y3RvcihyZWFzb246IEVycm9yUmVzcG9uc2VJbnRlcmZhY2UsIC4uLmFyZ3M6IGFueVtdKSB7XG4gICAgICAgIHN1cGVyKC4uLmFyZ3MpO1xuXG4gICAgICAgIHRoaXMucmVhc29uID0gcmVhc29uO1xuICAgICAgICBpZiAocmVhc29uLmVycm9yX2Rlc2NyaXB0aW9uICE9PSBudWxsICYmIHJlYXNvbi5lcnJvcl9kZXNjcmlwdGlvbiAhPT0gdW5kZWZpbmVkKSB7XG4gICAgICAgICAgICB0aGlzLm1lc3NhZ2UgPSByZWFzb24uZXJyb3JfZGVzY3JpcHRpb247XG4gICAgICAgIH1cbiAgICB9XG59XG4iXX0=