## Summary

Client side OAuth 2 implementation based on [RFC 6749 - The OAuth 2.0 Authorization Framework](https://tools.ietf.org/html/rfc6749).

Basic usage (complete sample below in [Usage section](#usage))

```javascript
const authorizer = ...;

// Request token

authorizer.password('user-name', 'password')
    .then(token => {
        // you got a token
    })
    .catch(error => {
        // something's broken
    });

// Refresh token

authorizer.refresh('old-token-value')
    .then(token => {
        // you got a new token
    })
    .catch(error => {
        // something's broken
    })

```

## Installation

```bash
$ npm install --save-dev @limoncello-framework/oauth-client
```

 or

```bash
$ yarn add --dev @limoncello-framework/oauth-client
```

## Features

RFC 6749 defines the following authorization grants

- [Authorization Code Grant](https://tools.ietf.org/html/rfc6749#section-4.1) - **implemented** (steps 4.1.3 and 4.1.4 of the spec).
- [Implicit Grant](https://tools.ietf.org/html/rfc6749#section-4.2) - out of the project scope (nothing to implement).
- [Resource Owner Password Credentials Grant](https://tools.ietf.org/html/rfc6749#section-4.3) - **implemented**.
- [Client Credentials Grant](https://tools.ietf.org/html/rfc6749#section-4.4) - **implemented**.

Additionally it describes [Refreshing an Access Token](https://tools.ietf.org/html/rfc6749#section-6) process - **implemented**.

## Usage

The library is designed to focus on the RFC logic and error handling leaving such minor technical details as sending data over network to a developer's choice. It requires from a developer to implement the following interface

```typescript
interface ClientRequestsInterface {
    /**
     * Sends form data to a OAuth Server token endpoint.
     */
    sendForm(data: any, addAuth: boolean): Promise<Response>;
}
```

`ClientRequestsInterface` could be implemented with [jQuery](https://api.jquery.com/jquery.post/), [XMLHttpRequest](https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest/send), [Fetch API]( https://developer.mozilla.org/en-US/docs/Web/API/WindowOrWorkerGlobalScope/fetch), [Node.js](https://nodejs.org/api/http.html) and etc. Here is an example built with Fetch API for `Resource Owner Password Credentials Grant` and `Refreshing an Access Token`.

```js
import { Authorizer } from '@limoncello-framework/oauth-client';

const clientRequests = {
    sendForm(data, addAuth) {
        // fill it a form
        // for more see https://developer.mozilla.org/en-US/docs/Web/API/FormData/FormData
        let form = new FormData();
        Object.getOwnPropertyNames(data).forEach((name) => form.append(name, data[name]));

        // see https://developer.mozilla.org/en-US/docs/Web/API/WindowOrWorkerGlobalScope/fetch
        // for a full list of available options.
        let init = {
            body: form,
            method: "post",
            mode: "cors",
            credentials: "omit",
            cache: "no-cache",
        };

        if (addAuth === true) {
            // add client auth info or throw an exception if not applicable
            init.headers = new Headers({Authorization: 'Basic ...'});
        }

        return fetch('http://your-domain.name/token', init);
    }
};

const authorizer = new Authorizer(clientRequests);

// Request token

authorizer.password('user-name', 'password', 'optional,list,of,scopes,separated,by,comma')
    .then(token => {
        // see https://tools.ietf.org/html/rfc6749#section-5.1
        console.log('token value ' + token.access_token);
        console.log('token will expire in (seconds) ' + token.expires_in);
        console.log('optional refresh token ' + token.refresh_token);
    })
    .catch(error => {
        if (error.reason !== undefined) {
            // see https://tools.ietf.org/html/rfc6749#section-5.2
            console.error('Authentication failed. Reason: ' + error.reason.error);
        } else {
            // invalid token URL, network error, invalid response format or server-side error
            console.error('Error occurred: ' + error.message);
        }
    });

// Refresh token

authorizer.refresh('old-token-value')
    .then(token => {
        // you got a new token
    })
    .catch(error => {
        // something's broken
    })
```

## Testing

- Clone the repository.
- Install dependencies with `npm install` or `yarn install`.
- Run tests with `npm run test` or `yarn test`.

## Questions

Feel free to open an issue marked as 'Question' in its title.
