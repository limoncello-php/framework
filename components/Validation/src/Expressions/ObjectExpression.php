<?php namespace Limoncello\Validation\Expressions;

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

use Limoncello\Validation\Contracts\RuleInterface;
use Limoncello\Validation\Rules;

/**
 * @package Limoncello\Validation
 */
class ObjectExpression extends IterateRules
{
    /**
     * @var RuleInterface
     */
    private $unlisted;

    /**
     * @param array         $rules
     * @param RuleInterface $unlisted
     */
    public function __construct(array $rules, RuleInterface $unlisted)
    {
        parent::__construct($rules);

        $this->unlisted = $unlisted;
        $this->unlisted->setParentRule($this);
    }

    /**
     * @inheritdoc
     */
    public function validate($input)
    {
        foreach (get_object_vars($input) as $key => $value) {
            $this->setParameterName($key);
            $rules = $this->getRules();
            $rule  = array_key_exists($key, $rules) === true ? $rules[$key] : $this->unlisted;
            $rule->setParentRule($this);
            foreach ($rule->validate($value) as $error) {
                yield $error;
            }
        }
    }
}
