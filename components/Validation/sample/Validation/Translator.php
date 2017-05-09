<?php namespace Sample\Validation;

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

use Limoncello\Validation\Contracts\MessageCodes;
use Limoncello\Validation\I18n\Locales\EnUsLocale;
use Limoncello\Validation\I18n\Translator as BaseTranslator;

/**
 * @package Sample
 */
class Translator extends BaseTranslator
{
    /** Custom error code */
    const IS_EMAIL = 1000001;

    /** Custom error code */
    const IS_EXISTING_PAYMENT_PLAN = 1000002;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // sample replacements
        $replacements = [
            'email'        => 'Email address',
            'first_name'   => 'First Name',
            'last_name'    => 'Last Name',
            'payment_plan' => 'Payment plan',
            'interests'    => 'Interests',
        ];

        // sample custom messages
        $messages = EnUsLocale::getMessages() + [
                static::IS_EMAIL                 => "The `{0}` value should be a valid email address.",
                static::IS_EXISTING_PAYMENT_PLAN => "The `{0}` value should be an existing payment plan.",
            ];

        parent::__construct(EnUsLocale::getLocaleCode(), $messages, $replacements, MessageCodes::INVALID_VALUE);
    }
}
