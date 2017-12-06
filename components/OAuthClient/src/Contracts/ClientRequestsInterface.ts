/**
 * A basic wrapper for fetching forms.
 */
export default interface ClientRequestsInterface {
    /**
     * Sends form data to a OAuth Server token endpoint.
     *
     * Required for
     * - Resource Owner Password Credentials Grant (https://tools.ietf.org/html/rfc6749#section-4.3)
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/API/WindowOrWorkerGlobalScope/fetch
     * @link https://developer.mozilla.org/en-US/docs/Web/API/FormData/FormData
     */
    sendForm(data: any, addAuth: boolean): Promise<Response>;
}
