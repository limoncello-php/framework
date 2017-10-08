<?php namespace Limoncello\Application\Packages\Monolog;

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

use Limoncello\Contracts\Settings\SettingsInterface;
use Monolog\Logger;

/**
 * @package Limoncello\Application
 */
class MonologFileSettings implements SettingsInterface
{
    /** Settings key */
    const KEY_IS_ENABLED = 0;

    /** Settings key */
    const KEY_LOG_PATH = self::KEY_IS_ENABLED + 1;

    /** Settings key */
    const KEY_LOG_FOLDER = self::KEY_LOG_PATH + 1;

    /** Settings key */
    const KEY_LOG_FILE = self::KEY_LOG_FOLDER + 1;

    /** Settings key */
    const KEY_LOG_LEVEL = self::KEY_LOG_FILE + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_LOG_LEVEL;

    /**
     * @inheritdoc
     */
    final public function get(): array
    {
        $defaults = $this->getSettings();

        $logFolder = $defaults[static::KEY_LOG_FOLDER] ?? null;
        $logFile   = $defaults[static::KEY_LOG_FILE] ?? null;
        assert(
            $logFolder !== null && empty(glob($logFolder)) === false,
            "Invalid Logs folder `$logFolder`."
        );
        assert(empty($logFile) === false, "Invalid Logs file name `$logFile`.");

        $logPath = $logFolder . DIRECTORY_SEPARATOR . $logFile;

        return $defaults + [static::KEY_LOG_PATH => $logPath];
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        return [
            static::KEY_IS_ENABLED => false,
            static::KEY_LOG_LEVEL  => Logger::DEBUG,
            static::KEY_LOG_FILE   => 'limoncello.log',
        ];
    }
}
