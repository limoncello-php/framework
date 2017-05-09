<?php namespace Sample;

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

use Limoncello\Validation\Errors\Error;
use Sample\Validation\Translator;
use Sample\Validation\Validator as v;

/**
 * @package Sample
 */
class Application
{
    /**
     * @var bool
     */
    private $isOutputToConsole;

    /**
     * @param bool $isOutputToConsole
     */
    public function __construct($isOutputToConsole = true)
    {
        $this->isOutputToConsole = $isOutputToConsole;
    }

    /**
     * @return void
     */
    public function run()
    {
        // Validation rules for input are
        // - `email` must be a string and a valid email value (as FILTER_VALIDATE_EMAIL describes)
        // - `first_name` required in input, must be a string with length from 1 to 255 characters
        // - `last_name` could be either `null` or if given it must be a string with length from 1 to 255 characters
        // - `payment_plan` must be a valid index for data in database (we will emulate request to database)
        // - `interests` must be an array of non-empty strings (any number of items, no limit for max length)

        $invalidInput = [
            'email'        => 'john.dow',
            //'first_name' => 'John',
            'last_name'    => '',
            'payment_plan' => 123,
            'interests'    => ['leisure', false, 'php', 321],
        ];

        $validInput = [
            'email'        => 'john@dow.com',
            'first_name'   => 'John',
            'last_name'    => null,
            'payment_plan' => 2,
            'interests'    => ['leisure', 'php', 'programming'],
        ];

        // Having app specific rules separated makes the code easier to read and reuse.
        // Though you can have the rules in-lined.

        $rules = [
            'email'        => v::isEmail(),
            'first_name'   => v::isRequiredString(255),
            'last_name'    => v::isNullOrNonEmptyString(255),
            'payment_plan' => v::isExistingPaymentPlan(),
            'interests'    => v::isListOfStrings(),
        ];

        $this->console('Invalid data' . PHP_EOL);
        $this->printErrors(
            v::validator(v::arrayX($rules))->validate($invalidInput)
        );

        $this->console(PHP_EOL . 'Valid data' . PHP_EOL);
        $this->printErrors(
            v::validator(v::arrayX($rules))->validate($validInput)
        );

        // Note that error message placeholders like `first_name` are replaced with
        // more readable such as `First Name` and others.
        //
        // The output would be
        // -------------------------------------------------------------------------------------------------------
        // Invalid data
        // Param `email` failed for `john.dow` with: The `Email address` value should be a valid email address.
        // Param `last_name` failed for `` with: The `Last Name` value should be between 1 and 255 characters.
        // Param `payment_plan` failed for `123` with: The `Payment plan` value should be an existing payment plan.
        // Param `interests` failed for `` with: The `Interests` value should be a string.
        // Param `interests` failed for `321` with: The `Interests` value should be a string.
        // Param `first_name` failed for `` with: The `First Name` value is required.
        //
        // Valid data
        // No errors
        // -------------------------------------------------------------------------------------------------------
    }

    /**
     * @param $errors
     */
    private function printErrors($errors)
    {
        $hasErrors  = false;
        $translator = new Translator();

        foreach ($errors as $error) {
            $hasErrors = true;
            /** @var Error $error */
            $paramName  = $error->getParameterName();
            $paramValue = $error->getParameterValue();
            $errorMsg   = $translator->translate($error);

            $this->console("Param `$paramName` failed for `$paramValue` with: $errorMsg" . PHP_EOL);
        }

        if ($hasErrors === false) {
            $this->console('No errors' . PHP_EOL);
        }
    }

    /**
     * @param string $string
     */
    private function console($string)
    {
        if ($this->isOutputToConsole === true) {
            echo $string;
        }
    }
}
