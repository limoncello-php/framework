<?php namespace Limoncello\Flute\Http;

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

use Limoncello\Flute\Contracts\Api\ModelsDataInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\SupportedExtensionsInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Neomerx\JsonApi\Http\Responses as JsonApiResponses;

/**
 * @package Limoncello\Flute
 */
class Responses extends JsonApiResponses
{
    /**
     * @var EncodingParametersInterface|null
     */
    private $parameters;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var MediaTypeInterface
     */
    private $outputMediaType;

    /**
     * @var SupportedExtensionsInterface
     */
    private $extensions;

    /**
     * @var ContainerInterface
     */
    private $schemes;

    /**
     * @var null|string
     */
    private $urlPrefix;

    /**
     * @param MediaTypeInterface               $outputMediaType
     * @param SupportedExtensionsInterface     $extensions
     * @param EncoderInterface                 $encoder
     * @param ContainerInterface               $schemes
     * @param EncodingParametersInterface|null $parameters
     * @param string|null                      $urlPrefix
     */
    public function __construct(
        MediaTypeInterface $outputMediaType,
        SupportedExtensionsInterface $extensions,
        EncoderInterface $encoder,
        ContainerInterface $schemes,
        EncodingParametersInterface $parameters = null,
        $urlPrefix = null
    ) {
        $this->extensions      = $extensions;
        $this->encoder         = $encoder;
        $this->outputMediaType = $outputMediaType;
        $this->schemes         = $schemes;
        $this->urlPrefix       = $urlPrefix;
        $this->parameters      = $parameters;
    }

    /**
     * @inheritdoc
     */
    public function getContentResponse(
        $data,
        $statusCode = JsonApiResponses::HTTP_OK,
        $links = null,
        $meta = null,
        array $headers = []
    ) {
        return parent::getContentResponse($data, $statusCode, $links, $meta);
    }

    /**
     * @inheritdoc
     */
    protected function createResponse($content, $statusCode, array $headers)
    {
        return new JsonApiResponse($content, $statusCode, $headers);
    }

    /**
     * @inheritdoc
     */
    protected function getEncoder()
    {
        return $this->encoder;
    }

    /**
     * @inheritdoc
     */
    protected function getUrlPrefix()
    {
        return $this->urlPrefix;
    }

    /**
     * @inheritdoc
     */
    protected function getEncodingParameters()
    {
        return $this->parameters;
    }

    /**
     * @inheritdoc
     */
    protected function getSchemaContainer()
    {
        return $this->schemes;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedExtensions()
    {
        return $this->extensions;
    }

    /**
     * @inheritdoc
     */
    protected function getMediaType()
    {
        return $this->outputMediaType;
    }

    /**
     * @inheritdoc
     */
    protected function getResourceLocationUrl($resource)
    {
        if ($resource instanceof ModelsDataInterface) {
            $resource = $resource->getPaginatedData();
        }

        if ($resource instanceof PaginatedDataInterface) {
            $resource = $resource->getData();
        }

        return parent::getResourceLocationUrl($resource);
    }
}
