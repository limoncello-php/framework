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

use Psr\Http\Message\ResponseInterface;

/**
 * @package Limoncello\Testing
 */
class Sapi extends \Limoncello\Core\Application\Sapi
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /** @noinspection PhpMissingParentCallCommonInspection
     * @inheritdoc
     */
    public function handleResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
