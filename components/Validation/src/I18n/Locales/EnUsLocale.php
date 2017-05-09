<?php namespace Limoncello\Validation\I18n\Locales;

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

use Limoncello\Validation\Contracts\LocaleInterface;
use Limoncello\Validation\Contracts\MessageCodes;
use Limoncello\Validation\I18n\Translator;
use Limoncello\Validation\Rules\Between;
use Limoncello\Validation\Rules\IsDateTimeFormat;
use Limoncello\Validation\Rules\StringLength;

/**
 * @package Limoncello\Validation
 */
class EnUsLocale implements LocaleInterface
{
    /**
     * @inheritdoc
     */
    public static function getLocaleCode(): string
    {
        return 'en_US';
    }

    /**
     * @inheritdoc
     */
    public static function getMessages(): array
    {
        $dateTimeIdx        = Translator::CONTEXT_PARAMS_SHIFT + IsDateTimeFormat::CONTEXT_FORMAT;
        $stringLengthMinIdx = Translator::CONTEXT_PARAMS_SHIFT + StringLength::CONTEXT_MIN;
        $stringLengthMaxIdx = Translator::CONTEXT_PARAMS_SHIFT + StringLength::CONTEXT_MAX;
        $betweenMinIdx      = Translator::CONTEXT_PARAMS_SHIFT + Between::CONTEXT_MIN;
        $betweenMaxIdx      = Translator::CONTEXT_PARAMS_SHIFT + Between::CONTEXT_MAX;

        /** @see http://php.net/manual/en/messageformatter.formatmessage.php for supported format options */
        /** @see \Limoncello\Validation\I18n\Translator::translate for parameters order */
        return [
            MessageCodes::INVALID_VALUE       => "The `{0}` value is invalid.",
            MessageCodes::IS_STRING           => "The `{0}` value should be a string.",
            MessageCodes::IS_BOOL             => "The `{0}` value should be boolean.",
            MessageCodes::IS_INT              => "The `{0}` value should be integer.",
            MessageCodes::IS_FLOAT            => "The `{0}` value should be float.",
            MessageCodes::IS_NUMERIC          => "The `{0}` value should be numeric.",
            MessageCodes::IS_DATE_TIME        => "The `{0}` value should be a valid date time.",
            MessageCodes::IS_DATE_TIME_FORMAT => "The `{0}` value should be a date time in format `{{$dateTimeIdx}}`.",
            MessageCodes::IS_ARRAY            => "The `{0}` value should be an array.",
            MessageCodes::IS_NULL             => "The `{0}` value should be null.",
            MessageCodes::NOT_NULL            => "The `{0}` value should not be null.",
            MessageCodes::REQUIRED            => "The `{0}` value is required.",

            MessageCodes::STRING_LENGTH     =>
                "The `{0}` value should be between {{$stringLengthMinIdx}} and {{$stringLengthMaxIdx}} characters.",
            MessageCodes::STRING_LENGTH_MIN => "The `{0}` value should be at least {{$stringLengthMinIdx}} characters.",
            MessageCodes::STRING_LENGTH_MAX =>
                "The `{0}` value should not be greater than {{$stringLengthMaxIdx}} characters.",

            MessageCodes::BETWEEN     => "The `{0}` value should be between {{$betweenMinIdx}} and {{$betweenMaxIdx}}.",
            MessageCodes::BETWEEN_MIN => "The `{0}` value should be at least {{$betweenMinIdx}}.",
            MessageCodes::BETWEEN_MAX => "The `{0}` value should not be greater than {{$betweenMaxIdx}}.",
            MessageCodes::IN_VALUES   => "The `{0}` value is invalid.",
            MessageCodes::REG_EXP     => "The `{0}` value format is invalid.",
        ];
    }
}
