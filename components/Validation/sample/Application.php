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

use DateTime;
use DateTimeInterface;
use Limoncello\Validation\ArrayValidator as vv;
use Limoncello\Validation\Contracts\Errors\ErrorInterface;
use Limoncello\Validation\SingleValidator as v;
use MessageFormatter;
use Sample\Validation\CustomErrorMessages;
use Sample\Validation\CustomRules as r;

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
    public function __construct(bool $isOutputToConsole = true)
    {
        $this->isOutputToConsole = $isOutputToConsole;
    }

    /**
     * @return void
     */
    public function run(): void
    {
        $this->showSingleValueValidation();

        $this->showArrayValuesValidation();
    }

    /**
     * Shows single value validation with built-in rules.
     */
    private function showSingleValueValidation(): void
    {
        $this->console('Basic usage sample.' . PHP_EOL);
        $this->console('===================' . PHP_EOL);

        // Let's build a rule that validates an input to be either `null` or a string from 5 to 10 characters.
        $validator = v::validator(
            r::nullable(r::isString(r::stringLengthBetween(5, 10)))
        );

        // let's try validation with valid input
        $input = null;
        if ($validator->validate($input) === true) {
            $this->console("Validation OK for `null`." . PHP_EOL);
        } else {
            assert(false, 'We should not be here.');
        }
        // another one
        $input = 'Hello';
        if ($validator->validate($input) === true) {
            $this->console("Validation OK for `$input`." . PHP_EOL);
        } else {
            assert(false, 'We should not be here.');
        }
        // this one should not pass the validation
        $input = 'This string is too long.';
        if ($validator->validate($input) === false) {
            $this->console("Input `$input` has not passed validation." . PHP_EOL);
            $this->printErrors($validator->getErrors());
        } else {
            assert(false, 'We should not be here.');
        }

        // next example demonstrates
        // - parsing strings as dates
        // - validation for dates
        // - data capture so you don't need to parse the input second time after validation
        $fromDate  = new DateTime('2001-02-03');
        $toDate    = new DateTime('2001-04-05');
        $validator = v::validator(
            r::isString(r::stringToDateTime(DATE_ATOM, r::between($fromDate, $toDate)))
                ->setName('my_date')->enableCapture()
        );
        $input     = '2001-03-04T05:06:07+08:00';
        if ($validator->validate($input) === true) {
            $this->console("Validation OK for `$input`." . PHP_EOL);
            $myDate = $validator->getCaptures()['my_date'];
            // note that captured date is already DateTime
            assert($myDate instanceof DateTimeInterface);
        } else {
            assert(false, 'We should not be here.');
        }

        $this->console(PHP_EOL . PHP_EOL . PHP_EOL);

        // The output would be
        // -------------------------------------------------------------------------------------------------------
        // Basic usage sample.
        // ===================
        // Validation OK for `null`.
        // Validation OK for `Hello`.
        // Input `This string is too long to pass validation.` has not passed validation.
        // Validation failed for `This string is too long.` with: The value should be between 5 and 10 characters.
        // Validation OK for `2001-03-04T05:06:07+08:00`.
        // -------------------------------------------------------------------------------------------------------
    }

    /**
     * Shows validation for array values with custom rules.
     */
    private function showArrayValuesValidation(): void
    {
        $this->console('Advanced usage sample.' . PHP_EOL);
        $this->console('===================' . PHP_EOL);

        // Validation rules for input are
        // - `email` must be a string and a valid email value (as FILTER_VALIDATE_EMAIL describes)
        // - `first_name` required in input, must be a string with length from 1 to 255 characters
        // - `last_name` could be either `null` or if given it must be a string with length from 1 to 255 characters
        // - `payment_plan` must be a valid index for data in database (we will emulate request to database)
        $validator = vv::validator([
            'email'        => r::isEmail(),
            'first_name'   => r::isRequiredString(255),
            'last_name'    => r::isNullOrNonEmptyString(255),
            'payment_plan' => r::isExistingPaymentPlan(),
        ]);

        // Check with invalid data
        $invalidInput = [
            'email'        => 'john.dow',
            //'first_name' => 'John',
            'last_name'    => '',
            'payment_plan' => '123',
        ];
        $this->console('Invalid data (errors)' . PHP_EOL);
        $validator->validate($invalidInput);
        $this->printErrors($validator->getErrors());
        $this->console('Invalid data (captures)' . PHP_EOL);
        $this->printCaptures($validator->getCaptures());

        // Check with valid data
        $validInput = [
            'email'        => 'john@dow.com',
            'first_name'   => 'John',
            'last_name'    => null,
            'payment_plan' => '2',
        ];
        $this->console(PHP_EOL . 'Valid data (errors)' . PHP_EOL);
        $validator->validate($validInput);
        $this->printErrors($validator->getErrors());
        $this->console('Valid data (captures)' . PHP_EOL);
        $this->printCaptures($validator->getCaptures());

        // The output would be
        // -------------------------------------------------------------------------------------------------------
        // Advanced usage sample.
        // ===================
        // Invalid data (errors)
        // Param `email` failed for `john.dow` with: The value should be a valid email address.
        // Param `last_name` failed for `` with: The value should be between 1 and 255 characters.
        // Param `payment_plan` failed for `123` with: The value should be a valid payment plan.
        // Param `first_name` failed for `` with: The value is required.
        // Invalid data (captures)
        // No captures
        //
        // Valid data (errors)
        // No errors
        // Valid data (captures)
        // `email` = `john@dow.com` (string)
        // `first_name` = `John` (string)
        // `last_name` = `` (NULL)
        // `payment_plan` = `2` (integer)
        // -------------------------------------------------------------------------------------------------------
    }

    /**
     * @param ErrorInterface[] $errors
     *
     * @return void
     */
    private function printErrors(array $errors): void
    {
        $hasErrors = false;

        foreach ($errors as $error) {
            $hasErrors = true;
            $this->printError($error);
        }

        if ($hasErrors === false) {
            $this->console('No errors' . PHP_EOL);
        }
    }

    /**
     * @param ErrorInterface $error
     *
     * @return void
     */
    private function printError(ErrorInterface $error): void
    {
        $paramName  = $error->getParameterName();
        $entry      = empty($paramName) ? 'Validation' : "Param `$paramName`";
        $paramValue = $error->getParameterValue();
        $errorMsg   = CustomErrorMessages::MESSAGES[$error->getMessageCode()];
        $context    = $error->getMessageContext();
        $errorMsg   = MessageFormatter::formatMessage('en', $errorMsg, $context !== null ? $context : []);

        $this->console("$entry failed for `$paramValue` with: $errorMsg" . PHP_EOL);
    }

    /**
     * @param array $captures
     *
     * @return void
     */
    private function printCaptures(array $captures): void
    {
        $hasCaptures = false;

        foreach ($captures as $name => $value) {
            $hasCaptures = true;
            $type        = gettype($value);
            $this->console("`$name` = `$value` ($type)" . PHP_EOL);
        }

        if ($hasCaptures === false) {
            $this->console('No captures' . PHP_EOL);
        }
    }

    /**
     * @param string $string
     */
    private function console(string $string): void
    {
        if ($this->isOutputToConsole === true) {
            echo $string;
        }
    }
}
