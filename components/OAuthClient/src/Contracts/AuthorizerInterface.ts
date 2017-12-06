import TokenInterface from './TokenInterface';

/**
 * OAuth 2.0 authorization.
 *
 * @link https://tools.ietf.org/html/rfc6749#section-4
 */
export default interface AuthorizerInterface {
    /**
     * Resource Owner Password Credentials Grant.
     *
     * If redirectUri is given it will be included into request to OAuth server.
     * If clientId is given it will be included into *not authenticated* request to server.
     * If clientId is not given an *authenticated* (with client auth info) request will be sent to server.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.3
     */
    code(authCode: string, redirectUri?: string, clientId?: string): Promise<TokenInterface>;

    /**
     * Resource Owner Password Credentials Grant.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.3
     */
    password(userName: string, password: string, scope?: string): Promise<TokenInterface>;

    /**
     * Client Credentials Grant.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.4
     */
    client(scope?: string): Promise<TokenInterface>;

    /**
     * Refreshing an Access Token.
     *
     * @link https://tools.ietf.org/html/rfc6749#section-6
     */
    refresh(refreshToken: string, scope?: string): Promise<TokenInterface>;
}
