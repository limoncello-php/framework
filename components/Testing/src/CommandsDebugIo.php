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

use DateTimeImmutable;
use Exception;
use Limoncello\Contracts\Commands\IoInterface;

/**
 * @package Limoncello\Testing
 */
class CommandsDebugIo implements CommandsDebugIoInterface
{
    /**
     * @var array
     */
    private $arguments;

    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $records = [];

    /**
     * @param array $arguments
     * @param array $options
     */
    public function __construct(array $arguments = [], array $options = [])
    {
        $this->arguments = $arguments;
        $this->options   = $options;
    }

    /**
     * @inheritdoc
     */
    public function hasArgument(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    /**
     * @inheritdoc
     */
    public function getArgument(string $name)
    {
        return $this->arguments[$name];
    }

    /**
     * @inheritdoc
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @inheritdoc
     */
    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * @inheritdoc
     */
    public function getOption(string $name)
    {
        return $this->options[$name];
    }

    /**
     * @inheritdoc
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @inheritdoc
     *
     * @throws Exception
     */
    public function writeInfo(string $message, int $verbosity = self::VERBOSITY_NORMAL): IoInterface
    {
        return $this->addRecord(static::TYPE_INFO, $verbosity, $message);
    }

    /**
     * @inheritdoc
     *
     * @throws Exception
     */
    public function writeWarning(string $message, int $verbosity = self::VERBOSITY_NORMAL): IoInterface
    {
        return $this->addRecord(static::TYPE_WARNING, $verbosity, $message);
    }

    /**
     * @inheritdoc
     *
     * @throws Exception
     */
    public function writeError(string $message, int $verbosity = self::VERBOSITY_NORMAL): IoInterface
    {
        return $this->addRecord(static::TYPE_ERROR, $verbosity, $message);
    }

    /**
     * @inheritdoc
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * @inheritdoc
     */
    public function getInfoRecords(): array
    {
        return $this->filterRecordsByType($this->records, static::TYPE_INFO);
    }

    /**
     * @inheritdoc
     */
    public function getWarningRecords(): array
    {
        return $this->filterRecordsByType($this->records, static::TYPE_WARNING);
    }

    /**
     * @inheritdoc
     */
    public function getErrorRecords(): array
    {
        return $this->filterRecordsByType($this->records, static::TYPE_ERROR);
    }

    /**
     * @inheritdoc
     */
    public function clearRecords(): CommandsDebugIoInterface
    {
        $this->records = [];

        return $this;
    }

    /**
     * @param int    $type
     * @param int    $verbosity
     * @param string $message
     *
     * @return self
     *
     * @throws Exception
     */
    private function addRecord(int $type, int $verbosity, string $message): self
    {
        $this->records[] = [
            static::RECORD_KEY_TYPE      => $type,
            static::RECORD_KEY_VERBOSITY => $verbosity,
            static::RECORD_KEY_DATE_TIME => new DateTimeImmutable(),
            static::RECORD_KEY_MESSAGE   => $message,
        ];

        return $this;
    }

    /**
     * @param array $records
     * @param int   $type
     *
     * @return array
     */
    private function filterRecordsByType(array $records, int $type): array
    {
        $result = [];

        foreach ($records as $record) {
            if ($type === $record[static::RECORD_KEY_TYPE]) {
                $result[] = $record;
            }
        }

        return $result;
    }
}
