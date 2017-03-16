<?php namespace Limoncello\Validation\Captures;

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

use Limoncello\Validation\Contracts\CaptureAggregatorInterface;
use Limoncello\Validation\Contracts\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\RuleInterface;

/**
 * @package Limoncello\Validation
 */
abstract class BaseCapture implements RuleInterface
{
    /**
     * @var RuleInterface
     */
    private $rule;

    /**
     * @var bool
     */
    private $hadErrors = false;

    /**
     * If capture actually had some input data and did validation.
     *
     * @var bool
     */
    private $isValidated = false;

    /**
     * @var CaptureAggregatorInterface
     */
    private $aggregator;

    /**
     * @var string
     */
    private $name;

    /**
     * @param mixed $data
     *
     * @return void
     */
    abstract protected function capture($data);

    /**
     * @return mixed
     */
    abstract protected function getCapturedData();

    /**
     * @param string                     $name
     * @param RuleInterface              $rule
     * @param CaptureAggregatorInterface $aggregator
     */
    public function __construct($name, RuleInterface $rule, CaptureAggregatorInterface $aggregator)
    {
        $this->rule       = $rule;
        $this->aggregator = $aggregator;
        $this->name       = $name;
    }

    /**
     * @inheritdoc
     */
    public function validate($input)
    {
        $this->hadErrors = false;
        foreach ($this->getRule()->validate($input) as $error) {
            $this->hadErrors = true;
            yield $error;
        }

        if ($this->hadErrors() === false) {
            $this->capture($input);
        }

        $this->isValidated = true;
    }

    /**
     * @inheritdoc
     */
    public function isStateless()
    {
        return $this->getRule()->isStateless();
    }

    /**
     * @inheritdoc
     */
    public function setParentRule(RuleInterface $parent)
    {
        $this->getRule()->setParentRule($parent);
    }

    /**
     * @inheritdoc
     */
    public function getParameterName()
    {
        return $this->getRule()->getParameterName();
    }

    /**
     * @inheritdoc
     */
    public function setParameterName($parameterName)
    {
        $this->getRule()->setParameterName($parameterName);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function onFinish(ErrorAggregatorInterface $aggregator)
    {
        $this->getRule()->onFinish($aggregator);

        if ($this->isValidated() === true && $this->hadErrors() === false) {
            $this->getAggregator()->remember(
                $this->getCaptureName(),
                $this->getCapturedData()
            );
        }
    }

    /**
     * @return CaptureAggregatorInterface
     */
    protected function getAggregator()
    {
        return $this->aggregator;
    }

    /**
     * @return RuleInterface
     */
    protected function getRule()
    {
        return $this->rule;
    }

    /**
     * @return string
     */
    protected function getCaptureName()
    {
        return $this->name;
    }

    /**
     * @return boolean
     */
    protected function hadErrors()
    {
        return $this->hadErrors;
    }

    /**
     * @return boolean
     */
    protected function isValidated()
    {
        return $this->isValidated;
    }
}
