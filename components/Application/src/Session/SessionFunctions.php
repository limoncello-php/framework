<?php namespace Limoncello\Application\Session;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use ArrayIterator;
use Iterator;
use Limoncello\Application\Contracts\Session\SessionFunctionsInterface;

/**
 * @package Limoncello\Application
 *
 * @SuppressWarnings(PHPMD.Superglobals)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SessionFunctions implements SessionFunctionsInterface
{
    /**
     * @var callable
     */
    private $retrieveCallable;

    /**
     * @var callable
     */
    private $putCallable;

    /**
     * @var callable
     */
    private $hasCallable;

    /**
     * @var callable
     */
    private $deleteCallable;

    /**
     * @var callable
     */
    private $iteratorCallable;

    /**
     * @var callable
     */
    private $abortCallable;

    /**
     * @var callable
     */
    private $cacheLimiterCallable;

    /**
     * @var callable
     */
    private $createIdCallable;

    /**
     * @var callable
     */
    private $writeCloseCallable;

    /**
     * @var callable
     */
    private $unsetCallable;

    /**
     * @var callable
     */
    private $statusCallable;

    /**
     * @var callable
     */
    private $startCallable;

    /**
     * @var callable
     */
    private $setSaveHandlerCallable;

    /**
     * @var callable
     */
    private $setCookieParamsCallable;

    /**
     * @var callable
     */
    private $savePathCallable;

    /**
     * @var callable
     */
    private $resetCallable;

    /**
     * @var callable
     */
    private $registerShutdownCallable;

    /**
     * @var callable
     */
    private $regenerateIdCallable;

    /**
     * @var callable
     */
    private $nameCallable;

    /**
     * @var callable
     */
    private $moduleNameCallable;

    /**
     * @var callable
     */
    private $decodeCallable;

    /**
     * @var callable
     */
    private $destroyCallable;

    /**
     * @var callable
     */
    private $encodeCallable;

    /**
     * @var callable
     */
    private $gcCallable;

    /**
     * @var callable
     */
    private $getCookieParamsCallable;

    /**
     * @var callable
     */
    private $idCallable;

    /**
     * @var callable
     */
    private $cacheExpireCallable;

    /**
     * @var callable
     */
    private $couldBeStartedCallable;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this
            ->setRetrieveCallable(function (string $key) {
                return $_SESSION[$key];
            })
            ->setPutCallable(function (string $key, $serializable): void {
                $_SESSION[$key] = $serializable;
            })
            ->setHasCallable(function (string $key): bool {
                return array_key_exists($key, $_SESSION);
            })
            ->setDeleteCallable(function (string $key): void {
                unset($_SESSION[$key]);
            })
            ->setIteratorCallable(function (): Iterator {
                return new ArrayIterator($_SESSION);
            })
            ->setCouldBeStartedCallable(function (): bool {
                return session_status() === PHP_SESSION_NONE;
            })
            ->setAbortCallable('\session_abort')
            ->setCacheLimiterCallable('\session_cache_limiter')
            ->setCreateIdCallable('\session_create_id')
            ->setWriteCloseCallable('\session_write_close')
            ->setUnsetCallable('\session_unset')
            ->setStatusCallable('\session_status')
            ->setStartCallable('\session_start')
            ->setSetSaveHandlerCallable('\session_set_save_handler')
            ->setSetCookieParamsCallable('\session_set_cookie_params')
            ->setSavePathCallable('\session_save_path')
            ->setResetCallable('\session_reset')
            ->setRegisterShutdownCallable('\session_register_shutdown')
            ->setRegenerateIdCallable('\session_regenerate_id')
            ->setNameCallable('\session_name')
            ->setModuleNameCallable('\session_module_name')
            ->setDecodeCallable('\session_decode')
            ->setDestroyCallable('\session_destroy')
            ->setEncodeCallable('\session_encode')
            ->setGcCallable('\session_gc')
            ->setGetCookieParamsCallable('\session_get_cookie_params')
            ->setIdCallable('\session_id')
            ->setCacheExpireCallable('\session_cache_expire');
    }

    /**
     * @inheritdoc
     */
    public function getRetrieveCallable(): callable
    {
        return $this->retrieveCallable;
    }

    /**
     * @inheritdoc
     */
    public function setRetrieveCallable(callable $callable): SessionFunctionsInterface
    {
        $this->retrieveCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPutCallable(): callable
    {
        return $this->putCallable;
    }

    /**
     * @inheritdoc
     */
    public function setPutCallable(callable $callable): SessionFunctionsInterface
    {
        $this->putCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getHasCallable(): callable
    {
        return $this->hasCallable;
    }

    /**
     * @inheritdoc
     */
    public function setHasCallable(callable $callable): SessionFunctionsInterface
    {
        $this->hasCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDeleteCallable(): callable
    {
        return $this->deleteCallable;
    }

    /**
     * @inheritdoc
     */
    public function setDeleteCallable(callable $callable): SessionFunctionsInterface
    {
        $this->deleteCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getIteratorCallable(): callable
    {
        return $this->iteratorCallable;
    }

    /**
     * @inheritdoc
     */
    public function setIteratorCallable(callable $callable): SessionFunctionsInterface
    {
        $this->iteratorCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAbortCallable(): callable
    {
        return $this->abortCallable;
    }

    /**
     * @inheritdoc
     */
    public function setAbortCallable(callable $callable): SessionFunctionsInterface
    {
        $this->abortCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCacheExpireCallable(): callable
    {
        return $this->cacheExpireCallable;
    }

    /**
     * @inheritdoc
     */
    public function setCacheExpireCallable(callable $callable): SessionFunctionsInterface
    {
        $this->cacheExpireCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCacheLimiterCallable(): callable
    {
        return $this->cacheLimiterCallable;
    }

    /**
     * @inheritdoc
     */
    public function setCacheLimiterCallable(callable $callable): SessionFunctionsInterface
    {
        $this->cacheLimiterCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCreateIdCallable(): callable
    {
        return $this->createIdCallable;
    }

    /**
     * @inheritdoc
     */
    public function setCreateIdCallable(callable $callable): SessionFunctionsInterface
    {
        $this->createIdCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDecodeCallable(): callable
    {
        return $this->decodeCallable;
    }

    /**
     * @inheritdoc
     */
    public function setDecodeCallable(callable $callable): SessionFunctionsInterface
    {
        $this->decodeCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDestroyCallable(): callable
    {
        return $this->destroyCallable;
    }

    /**
     * @inheritdoc
     */
    public function setDestroyCallable(callable $callable): SessionFunctionsInterface
    {
        $this->destroyCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getEncodeCallable(): callable
    {
        return $this->encodeCallable;
    }

    /**
     * @inheritdoc
     */
    public function setEncodeCallable(callable $callable): SessionFunctionsInterface
    {
        $this->encodeCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getGcCallable(): callable
    {
        return $this->gcCallable;
    }

    /**
     * @inheritdoc
     */
    public function setGcCallable(callable $callable): SessionFunctionsInterface
    {
        $this->gcCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getGetCookieParamsCallable(): callable
    {
        return $this->getCookieParamsCallable;
    }

    /**
     * @inheritdoc
     */
    public function setGetCookieParamsCallable(callable $callable): SessionFunctionsInterface
    {
        $this->getCookieParamsCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getIdCallable(): callable
    {
        return $this->idCallable;
    }

    /**
     * @inheritdoc
     */
    public function setIdCallable(callable $callable): SessionFunctionsInterface
    {
        $this->idCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getModuleNameCallable(): callable
    {
        return $this->moduleNameCallable;
    }

    /**
     * @inheritdoc
     */
    public function setModuleNameCallable(callable $callable): SessionFunctionsInterface
    {
        $this->moduleNameCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getNameCallable(): callable
    {
        return $this->nameCallable;
    }

    /**
     * @inheritdoc
     */
    public function setNameCallable(callable $callable): SessionFunctionsInterface
    {
        $this->nameCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRegenerateIdCallable(): callable
    {
        return $this->regenerateIdCallable;
    }

    /**
     * @inheritdoc
     */
    public function setRegenerateIdCallable(callable $callable): SessionFunctionsInterface
    {
        $this->regenerateIdCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRegisterShutdownCallable(): callable
    {
        return $this->registerShutdownCallable;
    }

    /**
     * @inheritdoc
     */
    public function setRegisterShutdownCallable(callable $callable): SessionFunctionsInterface
    {
        $this->registerShutdownCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getResetCallable(): callable
    {
        return $this->resetCallable;
    }

    /**
     * @inheritdoc
     */
    public function setResetCallable(callable $callable): SessionFunctionsInterface
    {
        $this->resetCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSavePathCallable(): callable
    {
        return $this->savePathCallable;
    }

    /**
     * @inheritdoc
     */
    public function setSavePathCallable(callable $callable): SessionFunctionsInterface
    {
        $this->savePathCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSetCookieParamsCallable(): callable
    {
        return $this->setCookieParamsCallable;
    }

    /**
     * @inheritdoc
     */
    public function setSetCookieParamsCallable(callable $callable): SessionFunctionsInterface
    {
        $this->setCookieParamsCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSetSaveHandlerCallable(): callable
    {
        return $this->setSaveHandlerCallable;
    }

    /**
     * @inheritdoc
     */
    public function setSetSaveHandlerCallable(callable $callable): SessionFunctionsInterface
    {
        $this->setSaveHandlerCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStartCallable(): callable
    {
        return $this->startCallable;
    }

    /**
     * @inheritdoc
     */
    public function setStartCallable(callable $callable): SessionFunctionsInterface
    {
        $this->startCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatusCallable(): callable
    {
        return $this->statusCallable;
    }

    /**
     * @inheritdoc
     */
    public function setStatusCallable(callable $callable): SessionFunctionsInterface
    {
        $this->statusCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUnsetCallable(): callable
    {
        return $this->unsetCallable;
    }

    /**
     * @inheritdoc
     */
    public function setUnsetCallable(callable $callable): SessionFunctionsInterface
    {
        $this->unsetCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getWriteCloseCallable(): callable
    {
        return $this->writeCloseCallable;
    }

    /**
     * @inheritdoc
     */
    public function setWriteCloseCallable(callable $callable): SessionFunctionsInterface
    {
        $this->writeCloseCallable = $callable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCouldBeStartedCallable(): callable
    {
        return $this->couldBeStartedCallable;
    }

    /**
     * @inheritdoc
     */
    public function setCouldBeStartedCallable(callable $callable): SessionFunctionsInterface
    {
        $this->couldBeStartedCallable = $callable;

        return $this;
    }
}
