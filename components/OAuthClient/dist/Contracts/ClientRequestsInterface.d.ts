/**
 * A basic wrapper for fetching forms.
 */
export default interface ClientRequestsInterface {
    /**
     * Sends form data to a OAuth Server token endpoint.
     *
     * Could be easily implemented with Fetch API and FormsData.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/API/WindowOrWorkerGlobalScope/fetch
     * @link https://developer.mozilla.org/en-US/docs/Web/API/FormData/FormData
     */
    sendForm(data: any): Promise<Response>;
}
