<?php namespace Limoncello\Flute\Http;

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

use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\InjectContentTypeTrait;
use Zend\Diactoros\Stream;

/**
 * @package Limoncello\Flute
 */
class JsonApiResponse extends Response
{
    /** HTTP code */
    const HTTP_UNPROCESSABLE_ENTITY = 422;

    use InjectContentTypeTrait;

    /**
     * @param string|null $content
     * @param int         $status
     * @param array       $headers
     */
    public function __construct(string $content = null, int $status = 200, array $headers = [])
    {
        $body = new Stream('php://temp', 'wb+');

        if ($content !== null) {
            $body->write($content);
            $body->rewind();
        }

        // inject content-type even when there is no content otherwise
        // it would be set to 'text/html' by PHP/Web server/Browser
        $headers = $this->injectContentType(MediaTypeInterface::JSON_API_MEDIA_TYPE, $headers);

        parent::__construct($body, $status, $headers);
    }
}
