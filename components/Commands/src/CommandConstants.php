<?php declare(strict_types=1);

namespace Limoncello\Commands;

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

/**
 * @package Limoncello\Commands
 */
interface CommandConstants
{
    /**
     * When command is executed it creates a container from user application. On container creation
     * an HTTP verb (e.g. GET, PUT, etc) and HTTP path (e.g. '/homepage') could be specified so
     * the container could be configured for those verb and path.
     *
     * In order to give the application and idea that it's executed for command a special
     * HTTP verb and command name as HTTP path would be used as input parameters.
     * The verb is 'special' because it cannot collide with any HTTP verbs from HTTP server due to
     * it starts from special character in its name.
     *
     * According to https://tools.ietf.org/html/rfc2068#section-5.1.1 there are a few built-in
     * methods such as 'OPTIONS', 'GET', 'HEAD', 'POST' and others. Custom or so called
     * 'extension-methods' are also possible which should have syntax of a 'token'.
     *
     * According to https://tools.ietf.org/html/rfc2068#section-2.2 a 'token' should be
     *     1*<any CHAR except CTLs or tspecials>
     * (non empty string without CTL and tspecial characters)
     * where
     *     CTL       - any US-ASCII control character (octets 0 - 31) and DEL (127)
     *     tspecials - "(" | ")" | "<" | ">" | "@" | "," | ";" | ":" | "\" | <"> | "/" |
     *                 "[" | "]" | "?" | "=" | "{" | "}" |
     *                 US-ASCII SP, space (32) | US-ASCII HT, horizontal-tab (9)
     *
     * So if we start the verb from '>' we guarantee it will be collision free.
     */
    const HTTP_METHOD = '>COMMAND';

    /** Expected key at `composer.json` -> "extra" */
    const COMPOSER_JSON__EXTRA__APPLICATION = 'application';

    /** Expected key at `composer.json` -> "extra" -> "application" */
    const COMPOSER_JSON__EXTRA__APPLICATION__CLASS = 'class';

    /** Default application class name if not replaced via "extra" -> "application" -> "class" */
    const DEFAULT_APPLICATION_CLASS_NAME = '\\App\\Application';

    /** Expected key at `composer.json` -> "extra" -> "application" */
    const COMPOSER_JSON__EXTRA__APPLICATION__COMMANDS_CACHE = 'commands_cache';
}
