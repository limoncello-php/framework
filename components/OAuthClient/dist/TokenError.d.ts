import ErrorResponseInterface from './Contracts/ErrorResponseInterface';
/**
 * OAuth token error.
 */
export default class  extends Error {
    /**
     * OAuth Error details.
     */
    readonly reason: ErrorResponseInterface;
    /**
     * Constructor.
     */
    constructor(reason: ErrorResponseInterface, ...args: any[]);
}
