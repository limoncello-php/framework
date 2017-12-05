import { ErrorResponseCodes } from './ErrorResponseCodes';

/**
 * OAuth 2.0 token error description.
 *
 * @link https://tools.ietf.org/html/rfc6749#section-5.2
 */
export default interface ErrorResponseInterface {
    /**
     * Error code.
     */
    readonly error: ErrorResponseCodes;
    /**
     * Human-readable text providing additional information.
     */
    readonly error_description?: string;
    /**
     * A URI identifying a human-readable web page with information about the error.
     */
    readonly error_uri?: string;
}
