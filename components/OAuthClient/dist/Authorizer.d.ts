import AuthorizerInterface from './Contracts/AuthorizerInterface';
import TokenInterface from './Contracts/TokenInterface';
import ClientRequestsInterface from './Contracts/ClientRequestsInterface';
/**
 * @inheritdoc
 */
export default class  implements AuthorizerInterface {
    /**
     * Constructor.
     */
    constructor(requests: ClientRequestsInterface);
    /**
     * @inheritdoc
     */
    code(authCode: string, redirectUri?: string | undefined, clientId?: string | undefined): Promise<TokenInterface>;
    /**
     * @inheritdoc
     */
    password(userName: string, password: string, scope?: string): Promise<TokenInterface>;
    /**
     * @inheritdoc
     */
    client(scope?: string): Promise<TokenInterface>;
    /**
     * @inheritdoc
     */
    refresh(refreshToken: string, scope?: string): Promise<TokenInterface>;
    /**
     * Common code for parsing token responses.
     *
     * @param responsePromise
     */
    protected parseTokenResponse(responsePromise: Promise<Response>): Promise<TokenInterface>;
}
