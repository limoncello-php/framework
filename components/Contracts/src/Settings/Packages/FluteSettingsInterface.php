<?php namespace Limoncello\Contracts\Settings\Packages;

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

use Limoncello\Contracts\Settings\SettingsInterface;

/**
 * Provides individual settings for a component.
 *
 * @package Limoncello\Contracts
 */
interface FluteSettingsInterface extends SettingsInterface
{
    /** Config key */
    const KEY_DO_NOT_LOG_EXCEPTIONS_LIST = 0;

    /** Config key */
    const KEY_DO_NOT_LOG_EXCEPTIONS_LIST__AS_KEYS = self::KEY_DO_NOT_LOG_EXCEPTIONS_LIST + 1;

    /** Config key
     *
     * By default it checks that all Schemes have unique resource types. That's a legit case
     * to have multiple Schemes for a same resource type however it's more likely that developer
     * just forgot to set a unique one. If you do need multiple Schemes for a resource feel free
     * to set it to `false`.
     *
     * Default: true
     */
    const KEY_SCHEMES_REQUIRE_UNIQUE_TYPES = self::KEY_DO_NOT_LOG_EXCEPTIONS_LIST__AS_KEYS + 1;

    /** Config key */
    const KEY_SCHEMES_FOLDER = self::KEY_SCHEMES_REQUIRE_UNIQUE_TYPES + 1;

    /** Config key */
    const KEY_SCHEMES_FILE_MASK = self::KEY_SCHEMES_FOLDER + 1;

    /** Config key */
    const KEY_API_FOLDER = self::KEY_SCHEMES_FILE_MASK + 1;

    /** Config key */
    const KEY_ROUTES_FOLDER = self::KEY_API_FOLDER + 1;

    /** Config key */
    const KEY_JSON_VALIDATION_RULES_FOLDER = self::KEY_ROUTES_FOLDER + 1;

    /** Config key */
    const KEY_JSON_CONTROLLERS_FOLDER = self::KEY_JSON_VALIDATION_RULES_FOLDER + 1;

    /** Config key */
    const KEY_JSON_VALIDATORS_FOLDER = self::KEY_JSON_CONTROLLERS_FOLDER + 1;

    /** Config key */
    const KEY_JSON_VALIDATORS_FILE_MASK = self::KEY_JSON_VALIDATORS_FOLDER + 1;

    /** Config key */
    const KEY_FORM_VALIDATORS_FOLDER = self::KEY_JSON_VALIDATORS_FILE_MASK + 1;

    /** Config key */
    const KEY_FORM_VALIDATORS_FILE_MASK = self::KEY_FORM_VALIDATORS_FOLDER + 1;

    /** Config key */
    const KEY_QUERY_VALIDATORS_FOLDER = self::KEY_FORM_VALIDATORS_FILE_MASK + 1;

    /** Config key */
    const KEY_QUERY_VALIDATORS_FILE_MASK = self::KEY_QUERY_VALIDATORS_FOLDER + 1;

    /** Config key */
    const KEY_HTTP_CODE_FOR_UNEXPECTED_THROWABLE = self::KEY_QUERY_VALIDATORS_FILE_MASK + 1;

    /** Config key */
    const KEY_THROWABLE_TO_JSON_API_EXCEPTION_CONVERTER = self::KEY_HTTP_CODE_FOR_UNEXPECTED_THROWABLE + 1;

    /** Config key */
    const KEY_MODEL_TO_SCHEME_MAP = self::KEY_THROWABLE_TO_JSON_API_EXCEPTION_CONVERTER + 1;

    /** Config key */
    const KEY_JSON_VALIDATION_RULE_SETS_DATA = self::KEY_MODEL_TO_SCHEME_MAP + 1;

    /** Config key */
    const KEY_ATTRIBUTE_VALIDATION_RULE_SETS_DATA = self::KEY_JSON_VALIDATION_RULE_SETS_DATA + 1;

    /** Config key */
    const KEY_DEFAULT_PAGING_SIZE = self::KEY_ATTRIBUTE_VALIDATION_RULE_SETS_DATA + 1;

    /** Config key */
    const KEY_MAX_PAGING_SIZE = self::KEY_DEFAULT_PAGING_SIZE + 1;

    /** Config key */
    const KEY_JSON_ENCODE_OPTIONS = self::KEY_MAX_PAGING_SIZE + 1;

    /** Config key */
    const KEY_JSON_ENCODE_DEPTH = self::KEY_JSON_ENCODE_OPTIONS + 1;

    /** Config key */
    const KEY_IS_SHOW_VERSION = self::KEY_JSON_ENCODE_DEPTH + 1;

    /** Config key */
    const KEY_META = self::KEY_IS_SHOW_VERSION + 1;

    /** Config key */
    const KEY_URI_PREFIX = self::KEY_META + 1;

    /** Config key */
    const KEY_LAST = self::KEY_URI_PREFIX + 1;
}
