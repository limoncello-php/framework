import AuthorizerInterface from './Contracts/AuthorizerInterface';
import TokenInterface from './Contracts/TokenInterface';
import ClientRequestsInterface from './Contracts/ClientRequestsInterface';
import TokenError from './TokenError';
import ErrorResponseInterface from './Contracts/ErrorResponseInterface';

/**
 * Error message.
 */
const RESPONSE_IS_NOT_JSON: string =
    'Response is not in JSON format. It might be invalid token URL, network error, invalid response format or server-side error.';

/**
 * @inheritdoc
 */
export default class implements AuthorizerInterface {
    /**
     * Fetcher wrapper.
     *
     * @internal
     */
    private readonly requests: ClientRequestsInterface;

    /**
     * Constructor.
     */
    public constructor(requests: ClientRequestsInterface) {
        this.requests = requests;
    }

    /**
     * @inheritdoc
     */
    code(authCode: string, redirectUri?: string | undefined, clientId?: string | undefined): Promise<TokenInterface> {
        // NOTE: it implements steps 4.1.3 and 4.1.4 of the spec.
        // Steps 4.1.1 and 4.1.2 (getting authorization code) are outside of the implementation.
        // Technically those steps are plain redirect to authorization server (AS) and reading the code from URL in a
        // callback from AS. They are trivial and very specific to tools used for building the app.
        //
        // https://tools.ietf.org/html/rfc6749#section-4.1.3 and https://tools.ietf.org/html/rfc6749#section-4.1.4

        const addAuth: boolean = (clientId === undefined);
        const data: any = addAuth ?
            {
                grant_type: 'authorization_code',
                code: authCode
            } : {
                grant_type: 'authorization_code',
                code: authCode,
                client_id: clientId
            };
        if (redirectUri !== undefined) {
            data.redirect_uri = redirectUri;
        }

        return this.parseTokenResponse(this.requests.sendForm(data, addAuth));
    }

    /**
     * @inheritdoc
     */
    public password(userName: string, password: string, scope?: string): Promise<TokenInterface> {
        const data = scope !== undefined ?
            {
                grant_type: 'password',
                username: userName,
                password: password,
                scope: scope,
            } : {
                grant_type: 'password',
                username: userName,
                password: password,
            };

        return this.parseTokenResponse(this.requests.sendForm(data, false));
    }

    /**
     * @inheritdoc
     */
    public client(scope?: string): Promise<TokenInterface> {
        const data = scope !== undefined ?
            {
                grant_type: 'client_credentials',
                scope: scope
            } : {
                grant_type: 'client_credentials'
            };

        return this.parseTokenResponse(this.requests.sendForm(data, true));
    }

    /**
     * @inheritdoc
     */
    public refresh(refreshToken: string, scope?: string): Promise<TokenInterface> {
        const data = scope !== undefined ?
            {
                grant_type: 'refresh_token',
                refresh_token: refreshToken,
                scope: scope,
            } : {
                grant_type: 'refresh_token',
                refresh_token: refreshToken,
            };

        return this.parseTokenResponse(this.requests.sendForm(data, false));
    }

    /**
     * Common code for parsing token responses.
     *
     * @param responsePromise
     */
    protected parseTokenResponse(responsePromise: Promise<Response>): Promise<TokenInterface> {
        return responsePromise
            // here the response has only HTTP status but we want resolved JSON as well so...
            .then(response => Promise.all([
                response.json(),
                Promise.resolve(response.ok),
            ]))
            // ... now we have both
            .then(results => {
                const [json, isOk] = results;
                return isOk === true ?
                    Promise.resolve(<TokenInterface>json) :
                    Promise.reject(new TokenError(<ErrorResponseInterface>json));
            })
            // return the error from the block above or report the response was not JSON
            .catch((error: any) => Promise.reject(
                error.reason !== undefined ? error : new TypeError(RESPONSE_IS_NOT_JSON)
            ));
    }
}
