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
    public password(userName: string, password: string, scope?: string): Promise<TokenInterface> {
        const data = scope !== undefined ?
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

        return this.parseTokenResponse(this.requests.sendForm(data, false));
    }

    /**
     * @inheritdoc
     */
    public client(scope?: string): Promise<TokenInterface> {
        const data = scope !== undefined ?
            {
                'grant_type': 'client_credentials',
                'scope': scope
            } : {
                'grant_type': 'client_credentials'
            };

        return this.parseTokenResponse(this.requests.sendForm(data, true));
    }

    /**
     * @inheritdoc
     */
    public refresh(refreshToken: string, scope?: string): Promise<TokenInterface> {
        const data = scope !== undefined ?
            {
                'grant_type': 'refresh_token',
                'refresh_token': refreshToken,
                'scope': scope,
            } : {
                'grant_type': 'refresh_token',
                'refresh_token': refreshToken,
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
                if (isOk === false) {
                    throw new TokenError(<ErrorResponseInterface>json);
                }

                return Promise.resolve(<TokenInterface>json);
            })
            .catch((error: any) => {
                // rethrow the error from the block above
                if (error.reason !== undefined) {
                    throw error;
                }

                // if we are here the response was not JSON
                throw new TypeError(RESPONSE_IS_NOT_JSON);
            });
    }
}
