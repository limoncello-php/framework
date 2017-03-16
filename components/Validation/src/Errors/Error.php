<?php namespace Limoncello\Validation\Errors;

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

/**
 * @package Limoncello\Validation
 */
class Error implements ErrorInterface
{
    /**
     * @var string|null
     */
    private $name;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var int
     */
    private $code;

    /**
     * @var mixed
     */
    private $context;

    /**
     * @param null|string $name
     * @param mixed       $value
     * @param int         $code
     * @param mixed       $context
     */
    public function __construct($name, $value, $code, $context)
    {
        $this->name    = $name;
        $this->value   = $value;
        $this->code    = $code;
        $this->context = $context;
    }

    /**
     * @inheritdoc
     */
    public function getParameterName()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getParameterValue()
    {
        return $this->value;
    }

    /**
     * @inheritdoc
     */
    public function getMessageCode()
    {
        return $this->code;
    }

    /**
     * @inheritdoc
     */
    public function getMessageContext()
    {
        return $this->context;
    }
}
