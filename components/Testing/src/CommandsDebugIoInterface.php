<?php declare(strict_types=1);

namespace Limoncello\Testing;

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

use Limoncello\Contracts\Commands\IoInterface;

/**
 * @package Limoncello\Testing
 */
interface CommandsDebugIoInterface extends IoInterface
{
    /** @var int Log record key */
    const RECORD_KEY_TYPE = 0;

    /** @var int Log record key */
    const RECORD_KEY_VERBOSITY = self::RECORD_KEY_TYPE + 1;

    /** @var int Log record key */
    const RECORD_KEY_DATE_TIME = self::RECORD_KEY_VERBOSITY + 1;

    /** @var int Log record key */
    const RECORD_KEY_MESSAGE = self::RECORD_KEY_DATE_TIME + 1;

    /** @var int Log record type value */
    const TYPE_INFO = 0;

    /** @var int Log record type value */
    const TYPE_WARNING = self::TYPE_INFO + 1;

    /** @var int Log record type value */
    const TYPE_ERROR = self::TYPE_WARNING + 1;

    /**
     * @return array
     */
    public function getRecords(): array;

    /**
     * @return array
     */
    public function getInfoRecords(): array;

    /**
     * @return array
     */
    public function getWarningRecords(): array;

    /**
     * @return array
     */
    public function getErrorRecords(): array;

    /**
     * @return self
     */
    public function clearRecords(): self;
}
