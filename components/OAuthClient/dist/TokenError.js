"use strict";
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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiVG9rZW5FcnJvci5qcyIsInNvdXJjZVJvb3QiOiIvaG9tZS9uZW9tZXJ4L1Byb2plY3RzL2xpbW9uY2VsbG8vZnJhbWV3b3JrL2NvbXBvbmVudHMvT0F1dGhDbGllbnQvZGlzdC8iLCJzb3VyY2VzIjpbIlRva2VuRXJyb3IudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7Ozs7O0FBRUE7O0dBRUc7QUFDSDtJQUE2Qiw2QkFBSztJQU05Qjs7T0FFRztJQUNILG1CQUFtQixNQUE4QjtRQUFFLGNBQWM7YUFBZCxVQUFjLEVBQWQscUJBQWMsRUFBZCxJQUFjO1lBQWQsNkJBQWM7O1FBQWpFLCtCQUNhLElBQUksVUFNaEI7UUFKRyxLQUFJLENBQUMsTUFBTSxHQUFHLE1BQU0sQ0FBQztRQUNyQixFQUFFLENBQUMsQ0FBQyxNQUFNLENBQUMsaUJBQWlCLEtBQUssSUFBSSxJQUFJLE1BQU0sQ0FBQyxpQkFBaUIsS0FBSyxTQUFTLENBQUMsQ0FBQyxDQUFDO1lBQzlFLEtBQUksQ0FBQyxPQUFPLEdBQUcsTUFBTSxDQUFDLGlCQUFpQixDQUFDO1FBQzVDLENBQUM7O0lBQ0wsQ0FBQztJQUNMLGdCQUFDO0FBQUQsQ0FBQyxBQWpCRCxDQUE2QixLQUFLLEdBaUJqQyIsInNvdXJjZXNDb250ZW50IjpbImltcG9ydCBFcnJvclJlc3BvbnNlSW50ZXJmYWNlIGZyb20gJy4vQ29udHJhY3RzL0Vycm9yUmVzcG9uc2VJbnRlcmZhY2UnO1xuXG4vKipcbiAqIE9BdXRoIHRva2VuIGVycm9yLlxuICovXG5leHBvcnQgZGVmYXVsdCBjbGFzcyBleHRlbmRzIEVycm9yIHtcbiAgICAvKipcbiAgICAgKiBPQXV0aCBFcnJvciBkZXRhaWxzLlxuICAgICAqL1xuICAgIHB1YmxpYyByZWFkb25seSByZWFzb246IEVycm9yUmVzcG9uc2VJbnRlcmZhY2U7XG5cbiAgICAvKipcbiAgICAgKiBDb25zdHJ1Y3Rvci5cbiAgICAgKi9cbiAgICBwdWJsaWMgY29uc3RydWN0b3IocmVhc29uOiBFcnJvclJlc3BvbnNlSW50ZXJmYWNlLCAuLi5hcmdzOiBhbnlbXSkge1xuICAgICAgICBzdXBlciguLi5hcmdzKTtcblxuICAgICAgICB0aGlzLnJlYXNvbiA9IHJlYXNvbjtcbiAgICAgICAgaWYgKHJlYXNvbi5lcnJvcl9kZXNjcmlwdGlvbiAhPT0gbnVsbCAmJiByZWFzb24uZXJyb3JfZGVzY3JpcHRpb24gIT09IHVuZGVmaW5lZCkge1xuICAgICAgICAgICAgdGhpcy5tZXNzYWdlID0gcmVhc29uLmVycm9yX2Rlc2NyaXB0aW9uO1xuICAgICAgICB9XG4gICAgfVxufVxuIl19