<?php declare(strict_types=1);

namespace Sample;

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

use DateTime;
use DateTimeInterface;
use Limoncello\Validation\ArrayValidator as v;
use Limoncello\Validation\Contracts\Errors\ErrorInterface;
use MessageFormatter;
use Sample\Validation\Rules as r;

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
        $validator = v::validator([
            'sku'           => r::required(r::sku()),
            'amount'        => r::required(r::amount(5)),
            'delivery_date' => r::nullable(r::deliveryDate()),
            'email'         => r::email(),
            'address1'      => r::required(r::address1()),
            'address2'      => r::address2(),
            'accepted'      => r::required(r::areTermsAccepted()),
        ]);

        // Check with invalid data
        $invalidInput = [
            'sku'           => '123',
            'amount'        => '10',
            'delivery_date' => '2001-01-01',
            'email'         => 'john.dow',
            // 'address1'   => 'Dow 1', // missed required parameter
            'accepted'      => 'false',
        ];
        $this->console('Invalid data (errors)' . PHP_EOL);
        $validator->validate($invalidInput);
        $this->printErrors($validator->getErrors());
        $this->console('Invalid data (captures)' . PHP_EOL);
        $this->printCaptures($validator->getCaptures());

        // Check with valid data
        $validInput = [
            'sku'           => '1',
            'amount'        => '3',
            'delivery_date' => (new DateTime('+2 days'))->format(DateTime::ISO8601),
            'email'         => 'john.dow@mail.foo',
            'address1'      => 'Dow 1',
            'address2'      => null,
            'accepted'      => 'true',
        ];
        $this->console(PHP_EOL . 'Valid data (errors)' . PHP_EOL);
        $validator->validate($validInput);
        $this->printErrors($validator->getErrors());
        $this->console('Valid data (captures)' . PHP_EOL);
        $this->printCaptures($validator->getCaptures());

        // The output would be
        // -------------------------------------------------------------------------------------------------------
        // Invalid data (errors)
        // Param `sku` failed for `123` with: The value should be a valid SKU.
        // Param `amount` failed for `10` with: The value should be between 1 and 5.
        // Param `delivery_date` failed for `2001-01-01` with: The value should be a valid date time.
        // Param `email` failed for `john.dow` with: The value should be a valid email address.
        // Param `accepted` failed for `` with: The value should be equal to 1.
        // Param `address1` failed for `` with: The value is required.
        // Invalid data (captures)
        // No captures

        // Valid data (errors)
        // No errors
        // Valid data (captures)
        // `sku` = `1` (integer)
        // `amount` = `3` (integer)
        // `delivery_date` = `2018-01-04T15:07:33+0100` (object)
        // `email` = `john.dow@mail.foo` (string)
        // `address1` = `Dow 1` (string)
        // `address2` = `` (NULL)
        // `accepted` = `1` (boolean)
        // -------------------------------------------------------------------------------------------------------
    }

    /**
     * @param iterable $errors
     *
     * @return void
     */
    private function printErrors(iterable $errors): void
    {
        $hasErrors = false;

        foreach ($errors as $error) {
            $hasErrors = true;

            /** @var ErrorInterface $error */
            $paramName  = $error->getParameterName();
            $entry      = empty($paramName) ? 'Validation' : "Param `$paramName`";
            $paramValue = $error->getParameterValue();
            $errorMsg   = $error->getMessageTemplate();
            $context    = $error->getMessageParameters();
            $errorMsg   = MessageFormatter::formatMessage('en', $errorMsg, $context !== null ? $context : []);

            $this->console("$entry failed for `$paramValue` with: $errorMsg" . PHP_EOL);
        }

        if ($hasErrors === false) {
            $this->console('No errors' . PHP_EOL);
        }
    }

    /**
     * @param iterable $captures
     *
     * @return void
     */
    private function printCaptures(iterable $captures): void
    {
        $hasCaptures = false;

        foreach ($captures as $name => $value) {
            $hasCaptures = true;
            $type        = gettype($value);
            $value       = $value instanceof DateTimeInterface ? $value->format(DateTime::ISO8601) : $value;
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
