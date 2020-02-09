<?php declare(strict_types=1);

namespace Limoncello\Tests\Testing;

/**
 * Copyright 2015-2020 info@neomerx.com
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

use Exception;
use Limoncello\Testing\CommandsDebugIo;

/**
 * @package Limoncello\Tests\Testing
 */
class CommandsDebugIoTest extends TestCase
{
    /**
     * Test arguments and options.
     */
    public function testArgumentsAndOptions(): void
    {
        $arguments = ['arv1' => 'arg-value1'];
        $options   = ['opt1' => 'opt-value1'];

        $ioMock = new CommandsDebugIo($arguments, $options);

        $this->assertCount(1, $ioMock->getArguments());
        $this->assertTrue($ioMock->hasArgument('arv1'));
        $this->assertFalse($ioMock->hasArgument('arv2'));

        $this->assertCount(1, $ioMock->getArguments());
        $this->assertTrue($ioMock->hasArgument('arv1'));
        $this->assertSame('arg-value1', $ioMock->getArgument('arv1'));
        $this->assertFalse($ioMock->hasArgument('arv2'));

        $this->assertCount(1, $ioMock->getOptions());
        $this->assertTrue($ioMock->hasOption('opt1'));
        $this->assertSame('opt-value1', $ioMock->getOption('opt1'));
        $this->assertFalse($ioMock->hasOption('opt2'));
    }

    /**
     * Test arguments and options.
     *
     * @throws Exception
     */
    public function testIO(): void
    {
        $ioMock = new CommandsDebugIo();

        $assertRecords = function (int $errors, int $warnings, int $info) use ($ioMock): void {
            $this->assertTrue($errors >= 0);
            $this->assertTrue($warnings >= 0);
            $this->assertTrue($info >= 0);
            $this->assertCount($errors + $warnings + $info, $ioMock->getRecords());
            $this->assertCount($errors, $ioMock->getErrorRecords());
            $this->assertCount($warnings, $ioMock->getWarningRecords());
            $this->assertCount($info, $ioMock->getInfoRecords());
        };

        $assertRecords(0, 0, 0);

        $ioMock->writeError('whatever');
        $assertRecords(1, 0, 0);

        $ioMock->writeWarning('whatever');
        $assertRecords(1, 1, 0);

        $ioMock->writeInfo('whatever');
        $assertRecords(1, 1, 1);

        $ioMock->clearRecords();
        $assertRecords(0, 0, 0);
    }
}
