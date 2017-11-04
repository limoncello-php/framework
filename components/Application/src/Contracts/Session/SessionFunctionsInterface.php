<?php namespace Limoncello\Application\Contracts\Session;

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

/**
 * Provides a separation layer for native PHP session functions.
 *
 * @package Limoncello\Contracts
 */
interface SessionFunctionsInterface
{
    /**
     * @return callable
     */
    public function getRetrieveCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setRetrieveCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getPutCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setPutCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getHasCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setHasCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getDeleteCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setDeleteCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getIteratorCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setIteratorCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getAbortCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setAbortCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getCacheExpireCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setCacheExpireCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getCacheLimiterCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setCacheLimiterCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getCreateIdCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setCreateIdCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getDecodeCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setDecodeCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getDestroyCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setDestroyCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getEncodeCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setEncodeCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getGcCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setGcCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getGetCookieParamsCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setGetCookieParamsCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getIdCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setIdCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getModuleNameCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setModuleNameCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getNameCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setNameCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getRegenerateIdCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setRegenerateIdCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getRegisterShutdownCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setRegisterShutdownCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getResetCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setResetCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getSavePathCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setSavePathCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getSetCookieParamsCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setSetCookieParamsCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getSetSaveHandlerCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setSetSaveHandlerCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getStartCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setStartCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getStatusCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setStatusCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getUnsetCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setUnsetCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getWriteCloseCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setWriteCloseCallable(callable $callable): self;
}
