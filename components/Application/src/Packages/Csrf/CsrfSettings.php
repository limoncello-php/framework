<?php namespace Limoncello\Application\Packages\Csrf;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use Limoncello\Common\Reflection\CheckCallableTrait;
use Limoncello\Contracts\Settings\SettingsInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;

/**
 * @package Limoncello\Application
 */
class CsrfSettings implements SettingsInterface
{
    use CheckCallableTrait;

    /** @var string Default form field for storing CSRF value */
    public const DEFAULT_HTTP_REQUEST_CSRF_TOKEN_KEY = '_token';

    /** @var int Settings key */
    public const HTTP_METHODS_TO_CHECK = 0;

    /** @var int Settings key */
    public const HTTP_REQUEST_CSRF_TOKEN_KEY = self::HTTP_METHODS_TO_CHECK + 1;

    /** @var int Settings key */
    public const TOKEN_STORAGE_KEY_IN_SESSION = self::HTTP_REQUEST_CSRF_TOKEN_KEY + 1;

    /** @var int Settings key */
    public const MAX_TOKENS = self::TOKEN_STORAGE_KEY_IN_SESSION + 1;

    /** @var int Settings key */
    public const MAX_TOKENS_THRESHOLD = self::MAX_TOKENS + 1;

    /** @var int Settings key */
    public const CREATE_ERROR_RESPONSE_METHOD = self::MAX_TOKENS_THRESHOLD + 1;

    /** @var int Settings key */
    public const INTERNAL_HTTP_METHODS_TO_CHECK_AS_UC_KEYS = self::CREATE_ERROR_RESPONSE_METHOD + 1;

    /** @var int Settings key */
    public const KEY_LAST = self::INTERNAL_HTTP_METHODS_TO_CHECK_AS_UC_KEYS + 1;

    /**
     * @inheritdoc
     *
     * @throws ReflectionException
     */
    final public function get(array $appConfig): array
    {
        $settings = $this->getSettings();

        // check and transform HTTP methods
        $methods = $settings[static::HTTP_METHODS_TO_CHECK] ?? [];
        assert(empty($methods) === false);
        $upperCaseMethods = [];
        foreach ($methods as $method) {
            assert(is_string($method) === true && empty($method) === false);
            $upperCaseMethods[strtoupper($method)] = true;
        }
        $settings[static::INTERNAL_HTTP_METHODS_TO_CHECK_AS_UC_KEYS] = $upperCaseMethods;
        unset($settings[static::HTTP_METHODS_TO_CHECK]);

        // check token key
        $tokenKey = $settings[static::HTTP_REQUEST_CSRF_TOKEN_KEY];
        assert(is_string($tokenKey) === true && empty($tokenKey) === false);

        // check storage key
        $storageKey = $settings[static::TOKEN_STORAGE_KEY_IN_SESSION];
        assert(is_string($storageKey) === true && empty($storageKey) === false);

        // check max tokens
        $maxTokens = $settings[static::MAX_TOKENS];
        assert(is_null($maxTokens) === true || (is_int($maxTokens) === true && $maxTokens > 0));

        // check max tokens
        $maxTokensThreshold = $settings[static::MAX_TOKENS];
        assert(is_int($maxTokensThreshold) === true && $maxTokensThreshold >= 0);

        $errorResponseMethod = $settings[static::CREATE_ERROR_RESPONSE_METHOD] ?? null;
        $expectedArgs        = [ContainerInterface::class, ServerRequestInterface::class];
        $expectedRet         = ResponseInterface::class;
        assert(
            $this->checkPublicStaticCallable($errorResponseMethod, $expectedArgs, $expectedRet) === true,
            'CSRF error response method should have signature ' .
            '(ContainerInterface, ServerRequestInterface): ResponseInterface.'
        );

        return $settings;
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        // defaults
        $errorResponseMethod = [CsrfMiddleware::class, CsrfMiddleware::DEFAULT_ERROR_RESPONSE_METHOD];

        return [
            static::HTTP_METHODS_TO_CHECK        => ['POST', 'PUT', 'DELETE', 'PATCH'],
            static::HTTP_REQUEST_CSRF_TOKEN_KEY  => static::DEFAULT_HTTP_REQUEST_CSRF_TOKEN_KEY,
            static::TOKEN_STORAGE_KEY_IN_SESSION => 'csrf_tokens',
            static::MAX_TOKENS                   => 20,
            static::MAX_TOKENS_THRESHOLD         => 5,
            static::CREATE_ERROR_RESPONSE_METHOD => $errorResponseMethod,
        ];
    }
}
