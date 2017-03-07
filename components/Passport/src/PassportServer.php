<?php namespace Limoncello\Passport;

/**
 * Copyright 2015-2017 info@neomerx.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Limoncello\Passport\Entities\Token;
use Limoncello\Passport\Traits\BasicClientAuthenticationTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * @package Limoncello\Passport
 */
class PassportServer extends BasePassportServer
{
    use BasicClientAuthenticationTrait;

    // TODO move methods to traits

    /**
     * @inheritdoc
     */
    public function createCodeResponse(TokenInterface $code, string $state = null): ResponseInterface
    {
        assert($code instanceof Token);
        /** @var Token $code */

        $client = $this->getIntegration()->getClientRepository()->read($code->getClientIdentifier());
        if ($code->getRedirectUriString() === null ||
            in_array($code->getRedirectUriString(), $client->getRedirectUriStrings()) === false
        ) {
            return $this->getIntegration()->createInvalidClientAndRedirectUriErrorResponse();
        }

        $code->setCode($this->getIntegration()->generateCodeValue($code));

        $tokenRepo   = $this->getIntegration()->getTokenRepository();
        $createdCode = $tokenRepo->createCode($code);

        $response = $this->createRedirectCodeResponse($createdCode, $state);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function createTokenResponse(TokenInterface $token, string $state = null): ResponseInterface
    {
        assert($token instanceof Token);
        /** @var Token $token */

        $client = $this->getIntegration()->getClientRepository()->read($token->getClientIdentifier());
        if ($token->getRedirectUriString() === null ||
            in_array($token->getRedirectUriString(), $client->getRedirectUriStrings()) === false
        ) {
            return $this->getIntegration()->createInvalidClientAndRedirectUriErrorResponse();
        }

        list($tokenValue, $tokenType, $tokenExpiresIn) = $this->getIntegration()->generateTokenValues($token);

        // refresh value must be null by the spec
        $refreshValue = null;
        $token->setValue($tokenValue)->setType($tokenType)->setRefreshValue($refreshValue);
        $savedToken = $this->getIntegration()->getTokenRepository()->createToken($token);

        $response = $this->createRedirectTokenResponse($savedToken, $tokenExpiresIn, $state);

        return $response;
    }
}
