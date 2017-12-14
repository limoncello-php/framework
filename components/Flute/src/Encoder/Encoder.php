<?php namespace Limoncello\Flute\Encoder;

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

use Closure;
use Limoncello\Flute\Contracts\Adapters\PaginationStrategyInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;
use Psr\Http\Message\UriInterface;

/**
 * @package Limoncello\Flute
 */
class Encoder extends \Neomerx\JsonApi\Encoder\Encoder implements EncoderInterface
{
    /**
     * @var UriInterface
     */
    private $originalUri;

    /**
     * @inheritdoc
     */
    public function forOriginalUri(UriInterface $uri): EncoderInterface
    {
        $this->originalUri = $uri;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function encodeData($data, EncodingParametersInterface $parameters = null)
    {
        $data = $this->handleRelationshipStorageAndPagingData($data);

        return parent::encodeData($data, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function encodeIdentifiers($data, EncodingParametersInterface $parameters = null)
    {
        $data = $this->handleRelationshipStorageAndPagingData($data);

        return parent::encodeIdentifiers($data, $parameters);
    }

    /**
     * @return UriInterface
     */
    protected function getOriginalUri(): UriInterface
    {
        return $this->originalUri;
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    private function handleRelationshipStorageAndPagingData($data)
    {
        if ($data instanceof PaginatedDataInterface) {
            /** @var PaginatedDataInterface $data */
            $this->addPagingLinksIfNeeded($data);
            $data = $data->getData();
        }

        /** @var mixed $data */

        return $data;
    }

    /**
     * @param PaginatedDataInterface $data
     *
     * @return void
     */
    private function addPagingLinksIfNeeded(PaginatedDataInterface $data): void
    {
        if ($data->isCollection() === true &&
            (0 < $data->getOffset() || $data->hasMoreItems() === true) &&
            $this->getOriginalUri() !== null
        ) {
            $links       = [];
            $linkClosure = $this->createLinkClosure($data->getLimit());

            $prev = DocumentInterface::KEYWORD_PREV;
            $next = DocumentInterface::KEYWORD_NEXT;
            $data->getOffset() <= 0 ?: $links[$prev] = $linkClosure(max(0, $data->getOffset() - $data->getLimit()));
            $data->hasMoreItems() === false ?: $links[$next] = $linkClosure($data->getOffset() + $data->getLimit());

            $this->withLinks($links);
        }
    }

    /**
     * @param int $pageSize
     *
     * @return Closure
     */
    private function createLinkClosure(int $pageSize): Closure
    {
        assert($pageSize > 0);

        parse_str($this->getOriginalUri()->getQuery(), $queryParams);

        return function ($offset) use ($pageSize, $queryParams) {
            $paramsWithPaging = array_merge($queryParams, [
                QueryParametersParserInterface::PARAM_PAGE => [
                    PaginationStrategyInterface::PARAM_PAGING_OFFSET => $offset,
                    PaginationStrategyInterface::PARAM_PAGING_LIMIT  => $pageSize,
                ],
            ]);
            $newUri           = $this->getOriginalUri()->withQuery(http_build_query($paramsWithPaging));
            $fullUrl          = (string)$newUri;
            $link             = $this->getFactory()->createLink($fullUrl, null, true);

            return $link;
        };
    }
}
