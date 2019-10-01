<?php declare(strict_types=1);

namespace Limoncello\Application\ExceptionHandlers;

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

use Limoncello\Contracts\Http\ThrowableResponseInterface;
use Psr\Container\ContainerInterface;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use function call_user_func;

/**
 * @package Limoncello\Application
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class WhoopsThrowableHtmlHandler extends BaseThrowableHandler
{
    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function createResponse(Throwable $throwable, ContainerInterface $container): ThrowableResponseInterface
    {
        $message = 'Internal Server Error';

        $this->logException($throwable, $container, $message);

        list($isDebug, $appName, $exceptionDumper) = $this->getSettings($container);

        $status = $throwable->getCode() > 0 ? $throwable->getCode() : static::DEFAULT_HTTP_ERROR_CODE;

        if ($isDebug === true) {
            $run = new Run();

            // If these two options are not used it would work fine with PHP Unit and XDebug,
            // however it produces output to console under PhpDbg. So we need a couple of
            // tweaks to make it work predictably in both environments.
            //
            // this one forbids Whoops spilling output to console
            $run->writeToOutput(false);
            // by default after sending error to output Whoops stops execution
            // as we want just generated output `string` we instruct not to halt
            $run->allowQuit(false);

            $handler = new PrettyPageHandler();
            // without the line below Whoops is too smart and do not produce any output in tests
            $handler->handleUnconditionally(true);

            if ($exceptionDumper !== null) {
                $appSpecificDetails = call_user_func($exceptionDumper, $throwable, $container);
                $handler->addDataTable("$appName Details", $appSpecificDetails);
            }

            $handler->setPageTitle("Whoops! There was a problem with '$appName'.");
            $run->appendHandler($handler);

            $html     = $run->handleException($throwable);
            $response = $this->createThrowableHtmlResponse($throwable, $html, $status);
        } else {
            $response = $this->createThrowableTextResponse($throwable, $message, $status);
        }

        return $response;
    }
}
