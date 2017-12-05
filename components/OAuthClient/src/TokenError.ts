import ErrorResponseInterface from './Contracts/ErrorResponseInterface';

/**
 * OAuth token error.
 */
export default class extends Error {
    /**
     * OAuth Error details.
     */
    public readonly reason: ErrorResponseInterface;

    /**
     * Constructor.
     */
    public constructor(reason: ErrorResponseInterface, ...args: any[]) {
        super(...args);

        this.reason = reason;
        if (reason.error_description !== null && reason.error_description !== undefined) {
            this.message = reason.error_description;
        }
    }
}
