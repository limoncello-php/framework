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
var default_1 = /** @class */ (function (_super) {
    __extends(default_1, _super);
    /**
     * Constructor.
     */
    function default_1(reason) {
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
    return default_1;
}(Error));
exports.default = default_1;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiVG9rZW5FcnJvci5qcyIsInNvdXJjZVJvb3QiOiIvaG9tZS9uZW9tZXJ4L1Byb2plY3RzL2xpbW9uY2VsbG8vZnJhbWV3b3JrL2NvbXBvbmVudHMvT0F1dGhDbGllbnQvZGlzdC8iLCJzb3VyY2VzIjpbIlRva2VuRXJyb3IudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IjtBQUFBOzs7Ozs7Ozs7Ozs7OztHQWNHOzs7Ozs7Ozs7Ozs7QUFJSDs7R0FFRztBQUNIO0lBQTZCLDZCQUFLO0lBTTlCOztPQUVHO0lBQ0gsbUJBQW1CLE1BQThCO1FBQUUsY0FBYzthQUFkLFVBQWMsRUFBZCxxQkFBYyxFQUFkLElBQWM7WUFBZCw2QkFBYzs7UUFBakUsK0JBQ2EsSUFBSSxVQU1oQjtRQUpHLEtBQUksQ0FBQyxNQUFNLEdBQUcsTUFBTSxDQUFDO1FBQ3JCLEVBQUUsQ0FBQyxDQUFDLE1BQU0sQ0FBQyxpQkFBaUIsS0FBSyxJQUFJLElBQUksTUFBTSxDQUFDLGlCQUFpQixLQUFLLFNBQVMsQ0FBQyxDQUFDLENBQUM7WUFDOUUsS0FBSSxDQUFDLE9BQU8sR0FBRyxNQUFNLENBQUMsaUJBQWlCLENBQUM7UUFDNUMsQ0FBQzs7SUFDTCxDQUFDO0lBQ0wsZ0JBQUM7QUFBRCxDQUFDLEFBakJELENBQTZCLEtBQUssR0FpQmpDIiwic291cmNlc0NvbnRlbnQiOlsiLyoqXG4gKiBDb3B5cmlnaHQgMjAxNS0yMDE3IGluZm9AbmVvbWVyeC5jb21cbiAqXG4gKiBMaWNlbnNlZCB1bmRlciB0aGUgQXBhY2hlIExpY2Vuc2UsIFZlcnNpb24gMi4wICh0aGUgXCJMaWNlbnNlXCIpO1xuICogeW91IG1heSBub3QgdXNlIHRoaXMgZmlsZSBleGNlcHQgaW4gY29tcGxpYW5jZSB3aXRoIHRoZSBMaWNlbnNlLlxuICogWW91IG1heSBvYnRhaW4gYSBjb3B5IG9mIHRoZSBMaWNlbnNlIGF0XG4gKlxuICogaHR0cDovL3d3dy5hcGFjaGUub3JnL2xpY2Vuc2VzL0xJQ0VOU0UtMi4wXG4gKlxuICogVW5sZXNzIHJlcXVpcmVkIGJ5IGFwcGxpY2FibGUgbGF3IG9yIGFncmVlZCB0byBpbiB3cml0aW5nLCBzb2Z0d2FyZVxuICogZGlzdHJpYnV0ZWQgdW5kZXIgdGhlIExpY2Vuc2UgaXMgZGlzdHJpYnV0ZWQgb24gYW4gXCJBUyBJU1wiIEJBU0lTLFxuICogV0lUSE9VVCBXQVJSQU5USUVTIE9SIENPTkRJVElPTlMgT0YgQU5ZIEtJTkQsIGVpdGhlciBleHByZXNzIG9yIGltcGxpZWQuXG4gKiBTZWUgdGhlIExpY2Vuc2UgZm9yIHRoZSBzcGVjaWZpYyBsYW5ndWFnZSBnb3Zlcm5pbmcgcGVybWlzc2lvbnMgYW5kXG4gKiBsaW1pdGF0aW9ucyB1bmRlciB0aGUgTGljZW5zZS5cbiAqL1xuXG5pbXBvcnQgRXJyb3JSZXNwb25zZUludGVyZmFjZSBmcm9tICcuL0NvbnRyYWN0cy9FcnJvclJlc3BvbnNlSW50ZXJmYWNlJztcblxuLyoqXG4gKiBPQXV0aCB0b2tlbiBlcnJvci5cbiAqL1xuZXhwb3J0IGRlZmF1bHQgY2xhc3MgZXh0ZW5kcyBFcnJvciB7XG4gICAgLyoqXG4gICAgICogT0F1dGggRXJyb3IgZGV0YWlscy5cbiAgICAgKi9cbiAgICBwdWJsaWMgcmVhZG9ubHkgcmVhc29uOiBFcnJvclJlc3BvbnNlSW50ZXJmYWNlO1xuXG4gICAgLyoqXG4gICAgICogQ29uc3RydWN0b3IuXG4gICAgICovXG4gICAgcHVibGljIGNvbnN0cnVjdG9yKHJlYXNvbjogRXJyb3JSZXNwb25zZUludGVyZmFjZSwgLi4uYXJnczogYW55W10pIHtcbiAgICAgICAgc3VwZXIoLi4uYXJncyk7XG5cbiAgICAgICAgdGhpcy5yZWFzb24gPSByZWFzb247XG4gICAgICAgIGlmIChyZWFzb24uZXJyb3JfZGVzY3JpcHRpb24gIT09IG51bGwgJiYgcmVhc29uLmVycm9yX2Rlc2NyaXB0aW9uICE9PSB1bmRlZmluZWQpIHtcbiAgICAgICAgICAgIHRoaXMubWVzc2FnZSA9IHJlYXNvbi5lcnJvcl9kZXNjcmlwdGlvbjtcbiAgICAgICAgfVxuICAgIH1cbn1cbiJdfQ==