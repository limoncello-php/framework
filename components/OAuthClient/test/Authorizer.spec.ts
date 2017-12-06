import { Authorizer, ClientRequestsInterface, TokenInterface, ErrorResponseInterface, TokenError } from './../src';
import { expect } from 'chai';
import 'mocha';

class RequestsMock implements ClientRequestsInterface {
    private _data: any | undefined;
    private _headers: any | undefined;
    private readonly response: Response;

    public constructor(response: Response) {
        this._data = undefined;
        this._headers = undefined;
        this.response = response;
    }

    public sendForm(data: any, addAuth: boolean = false): Promise<Response> {
        expect(this._data, 'Mock cannot be used for more than one call.').to.be.undefined;
        expect(this._headers, 'Mock cannot be used for more than one call.').to.be.undefined;

        // semi-emulation of reading own object properties and adding them to form
        const form: any = {};
        Object.getOwnPropertyNames(data).forEach((name: string) => form[name] = data[name]);

        if (addAuth === true) {
            // emulate adding auth header
            this._headers = { Authorization: 'Basic <put auth info here>' };
        }

        this._data = form;

        return Promise.resolve(this.response);
    }

    public get data(): any | undefined {
        return this._data;
    }

    public get headers(): any | undefined {
        return this._headers;
    }
}

const prepareResponse = (isOk: boolean, jsonPromise: Promise<any>): Response => {
    return <Response>{
        ok: isOk,
        json: () => jsonPromise,
    };
}

const prepareInvalidJsonResponse = (): Response => {
    return <Response>{
        ok: true,
        json: () => JSON.parse('non json content'),
    };
}

describe('Authorizer component', () => {

    const TEST_TOKEN_VALUE = 'some-token';
    const tokenMock: TokenInterface = {
        token_type: 'example',
        access_token: TEST_TOKEN_VALUE
    };
    const errorMock: ErrorResponseInterface = {
        error: 'invalid_request',
    };

    it('should return token for a resource owner password grant request without scope', () => {
        const requestsMock = new RequestsMock(prepareResponse(true, Promise.resolve(tokenMock)));
        const authorizer = new Authorizer(requestsMock);

        authorizer.password('some-user-name', 'some-password')
            .then(token => expect(token.access_token).to.equal(TEST_TOKEN_VALUE))
            .catch(() => expect(false, 'It should not throw any errors.').to.be.true);

        expect(requestsMock.data).to.be.not.undefined;
        expect(requestsMock.data.grant_type).to.equal('password');
        expect(requestsMock.data.username).to.equal('some-user-name');
        expect(requestsMock.data.password).to.equal('some-password');
        expect(requestsMock.data.scope).to.be.undefined;
        expect(requestsMock.headers).to.be.undefined;
    });

    it('should return token for a resource owner password grant request with scope', () => {
        const requestsMock = new RequestsMock(prepareResponse(true, Promise.resolve(tokenMock)));
        const authorizer = new Authorizer(requestsMock);

        authorizer.password('some-user-name', 'some-password', 'scope1,scope2')
            .then(token => expect(token.access_token).to.equal(TEST_TOKEN_VALUE))
            .catch(() => expect(false, 'It should not throw any errors.').to.be.true);

        expect(requestsMock.data).to.be.not.undefined;
        expect(requestsMock.data.grant_type).to.equal('password');
        expect(requestsMock.data.username).to.equal('some-user-name');
        expect(requestsMock.data.password).to.equal('some-password');
        expect(requestsMock.data.scope).to.equal('scope1,scope2');
        expect(requestsMock.headers).to.be.undefined;
    });

    it('should return token for a client credentials grant request without scope', () => {
        const requestsMock = new RequestsMock(prepareResponse(true, Promise.resolve(tokenMock)));
        const authorizer = new Authorizer(requestsMock);

        authorizer.client()
            .then(token => expect(token.access_token).to.equal(TEST_TOKEN_VALUE))
            .catch(() => expect(false, 'It should not throw any errors.').to.be.true);

        expect(requestsMock.data).to.be.not.undefined;
        expect(requestsMock.data.grant_type).to.equal('client_credentials');
        expect(requestsMock.data.scope).to.be.undefined;
        expect(requestsMock.headers).to.be.not.undefined;
    });

    it('should return token for a client credentials grant request with scope', () => {
        const requestsMock = new RequestsMock(prepareResponse(true, Promise.resolve(tokenMock)));
        const authorizer = new Authorizer(requestsMock);

        authorizer.client('scope1,scope2')
            .then(token => expect(token.access_token).to.equal(TEST_TOKEN_VALUE))
            .catch(() => expect(false, 'It should not throw any errors.').to.be.true);

        expect(requestsMock.data).to.be.not.undefined;
        expect(requestsMock.data.grant_type).to.equal('client_credentials');
        expect(requestsMock.data.scope).to.equal('scope1,scope2');
        expect(requestsMock.headers).to.be.not.undefined;
    });

    it('should return token for a refresh token grant request without scope', () => {
        const requestsMock = new RequestsMock(prepareResponse(true, Promise.resolve(tokenMock)));
        const authorizer = new Authorizer(requestsMock);

        authorizer.refresh('some-token-value')
            .then(token => expect(token.access_token).to.equal(TEST_TOKEN_VALUE))
            .catch(() => expect(false, 'It should not throw any errors.').to.be.true);

        expect(requestsMock.data).to.be.not.undefined;
        expect(requestsMock.data.grant_type).to.equal('refresh_token');
        expect(requestsMock.data.refresh_token).to.equal('some-token-value');
        expect(requestsMock.data.scope).to.be.undefined;
    });

    it('should return token for a refresh token grant request with scope', () => {
        const requestsMock = new RequestsMock(prepareResponse(true, Promise.resolve(tokenMock)));
        const authorizer = new Authorizer(requestsMock);

        authorizer.refresh('some-token-value', 'scope1,scope2')
            .then(token => expect(token.access_token).to.equal(TEST_TOKEN_VALUE))
            .catch(() => expect(false, 'It should not throw any errors.').to.be.true);

        expect(requestsMock.data).to.be.not.undefined;
        expect(requestsMock.data.grant_type).to.equal('refresh_token');
        expect(requestsMock.data.refresh_token).to.equal('some-token-value');
        expect(requestsMock.data.scope).to.equal('scope1,scope2');
    });

    it('should return token error if server responded with failed response', () => {
        const fetcherMock = new RequestsMock(prepareResponse(false, Promise.resolve(errorMock)));
        const authorizer = new Authorizer(fetcherMock);

        authorizer.refresh('some-token-value')
            .then(() => expect(false, 'It should not return without any errors.').to.be.true)
            .catch((error: TokenError) => expect(error.reason).to.be.not.undefined);
    });

    it('should return type error if server responded with non JSON response', () => {
        const requestsMock = new RequestsMock(prepareInvalidJsonResponse());
        const authorizer = new Authorizer(requestsMock);

        authorizer.refresh('some-token-value')
            .then(() => expect(false, 'It should not return without any errors.').to.be.true)
            .catch((error: TypeError) => {
                expect(error.message).to.equal('Response is not in JSON format. It might be invalid token URL, network error, invalid response format or server-side error.');
            });
    });
});
