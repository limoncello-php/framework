import AuthorizerInterface from './Contracts/AuthorizerInterface';
import TokenInterface from './Contracts/TokenInterface';
import ClientRequestsInterface from './Contracts/ClientRequestsInterface';
/**
 * @inheritdoc
 */
export default class  implements AuthorizerInterface {
    /**
     * Fetcher wrapper.
     *
     * @internal
     */
    private readonly fetcher;
    /**
     * Constructor.
     */
    constructor(fetcher: ClientRequestsInterface);
    /**
     * @inheritdoc
     */
    password(userName: string, password: string, scope?: string): Promise<TokenInterface>;
    /**
     * @inheritdoc
     */
    refresh(refreshToken: string, scope?: string): Promise<TokenInterface>;
    /**
     * Fetch form to token endpoint.
     *
     * @internal
     */
    private fetchForm(data);
}
