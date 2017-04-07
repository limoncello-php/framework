<?php namespace Limoncello\Validation\I18n;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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

use Limoncello\Validation\Contracts\ErrorInterface;
use Limoncello\Validation\Contracts\MessageCodes;
use Limoncello\Validation\Contracts\TranslatorInterface;
use MessageFormatter;

/**
 * @package Limoncello\Validation
 */
class Translator implements TranslatorInterface
{
    /**
     * Translator merges parameter name and parameter value with data from error context.
     * This value sets number of positions to skip to get to context parameters.
     *
     * @see \Limoncello\Validation\I18n\Translator::translate
     */
    const CONTEXT_PARAMS_SHIFT = 2;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var array
     */
    private $messages;

    /**
     * @var int
     */
    private $defaultCode;

    /**
     * @var array
     */
    private $replacements;

    /**
     * @param string $locale
     * @param array  $messages
     * @param array  $replacements
     * @param int    $defaultCode
     */
    public function __construct(
        $locale,
        array $messages,
        array $replacements = [],
        $defaultCode = MessageCodes::INVALID_VALUE
    ) {
        $this->locale       = $locale;
        $this->messages     = $messages;
        $this->defaultCode  = $defaultCode;
        $this->replacements = $replacements;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function translate(ErrorInterface $error): string
    {
        $pattern    = $this->getMessagePattern($error->getMessageCode());
        $paramName  = $this->getParameterName($error->getParameterName());
        $value      = $error->getParameterValue();
        $paramValue = (is_scalar($value) === true || $value === null) ? $value : print_r($value, true);
        $parameters = [$paramName, $paramValue];
        $context    = $error->getMessageContext();
        if (empty($context) === false) {
            foreach ($context as $value) {
                // format message would fail even if one non scalar is passed
                if (is_scalar($value) === true || $value === null) {
                    $parameters[] = $value;
                }
            }
        }
        $message = MessageFormatter::formatMessage($this->locale, $pattern, $parameters);

        return $message;
    }

    /**
     * @return array
     */
    protected function getReplacements()
    {
        return $this->replacements;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getParameterName($name)
    {
        $replacements = $this->getReplacements();
        return array_key_exists($name, $replacements) === true ? $replacements[$name] : $name;
    }

    /**
     * @param int $messageCode
     *
     * @return string
     */
    private function getMessagePattern($messageCode)
    {
        $hasPattern = array_key_exists($messageCode, $this->messages) === true;
        $pattern    = $this->messages[$hasPattern === true ? $messageCode : $this->defaultCode];

        return $pattern;
    }
}
