<?php declare(strict_types=1);

namespace Limoncello\Passport\Authentication;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use Closure;
use Limoncello\Contracts\Application\MiddlewareInterface;
use Limoncello\Contracts\Passport\PassportAccountManagerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Passport\Exceptions\AuthenticationException;
use Limoncello\Passport\Package\PassportSettings as S;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Zend\Diactoros\Response\EmptyResponse;
use function assert;
use function call_user_func;
use function is_callable;
use function is_string;
use function substr;

/**
 * @package Limoncello\Passport
 */
class PassportMiddleware implements MiddlewareInterface
{
    /** Middleware handler */
    const HANDLER = [self::class, self::MIDDLEWARE_METHOD_NAME];

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public static function handle(
        ServerRequestInterface $request,
        Closure $next,
        ContainerInterface $container
    ): ResponseInterface {
        $header = $request->getHeader('Authorization');
        // if value has Bearer token and it is a valid json with 2 required fields and they are strings
        if (empty($header) === false &&
            substr($value = $header[0], 0, 7) === 'Bearer ' &&
            is_string($tokenValue = substr($value, 7)) === true &&
            empty($tokenValue) === false
        ) {
            assert($container->has(PassportAccountManagerInterface::class));

            /** @var PassportAccountManagerInterface $accountManager */
            $accountManager = $container->get(PassportAccountManagerInterface::class);
            try {
                $accountManager->setAccountWithTokenValue($tokenValue);
            } catch (AuthenticationException $exception) {
                if (($logger = static::getLoggerIfEnabled($container)) !== null) {
                    $logger->info(
                        'Passport authentication failed for a given Bearer token value.',
                        ['token' => $tokenValue]
                    );
                }

                return static::createAuthenticationFailedResponse($container);
            }
        } else {
            if (($logger = static::getLoggerIfEnabled($container)) !== null) {
                $logger->debug(
                    'No Bearer token for Passport authentication. The request is not authenticated.'
                );
            }
        }

        // call next middleware handler
        return $next($request);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return ResponseInterface
     */
    protected static function createAuthenticationFailedResponse(ContainerInterface $container): ResponseInterface
    {
        /** @var SettingsProviderInterface $provider */
        $provider = $container->get(SettingsProviderInterface::class);
        $settings = $provider->get(S::class);
        $factory  = $settings[S::KEY_FAILED_CUSTOM_UNAUTHENTICATED_FACTORY] ?? null;

        assert($factory === null || is_callable($factory) === true);

        $response = $factory === null ? new EmptyResponse(401) : call_user_func($factory);

        return $response;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return null|LoggerInterface
     */
    protected static function getLoggerIfEnabled(ContainerInterface $container): ?LoggerInterface
    {
        $logger = null;
        if ($container->has(LoggerInterface::class) === true &&
            $container->get(SettingsProviderInterface::class)->get(S::class)[S::KEY_IS_LOG_ENABLED] === true
        ) {
            $logger = $container->get(LoggerInterface::class);
        }

        return $logger;
    }
}
