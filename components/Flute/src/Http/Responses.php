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

use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Neomerx\JsonApi\Http\BaseResponses;

/**
 * @package Limoncello\Flute
 */
class Responses extends BaseResponses
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
     * @var ContainerInterface
     */
    private $schemes;

    /**
     * @var null|string
     */
    private $urlPrefix;

    /**
     * @var mixed|null
     */
    private $defaultMeta;

    /**
     * @param MediaTypeInterface               $outputMediaType
     * @param EncoderInterface                 $encoder
     * @param ContainerInterface               $schemes
     * @param EncodingParametersInterface|null $parameters
     * @param string|null                      $urlPrefix
     * @param mixed|null                       $defaultMeta
     */
    public function __construct(
        MediaTypeInterface $outputMediaType,
        EncoderInterface $encoder,
        ContainerInterface $schemes,
        EncodingParametersInterface $parameters = null,
        string $urlPrefix = null,
        $defaultMeta = null
    ) {
        $this->encoder         = $encoder;
        $this->outputMediaType = $outputMediaType;
        $this->schemes         = $schemes;
        $this->urlPrefix       = $urlPrefix;
        $this->parameters      = $parameters;
        $this->defaultMeta     = $defaultMeta;
    }

    /**
     * @inheritdoc
     */
    public function getContentResponse(
        $data,
        int $statusCode = self::HTTP_OK,
        array $links = null,
        $meta = null,
        array $headers = []
    ) {
        return parent::getContentResponse($data, $statusCode, $links, $this->mergeDefaultMeta($meta), $headers);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedResponse($resource, array $links = null, $meta = null, array $headers = [])
    {
        return parent::getCreatedResponse($resource, $links, $this->mergeDefaultMeta($meta), $headers);
    }

    /**
     * @inheritdoc
     */
    public function getIdentifiersResponse(
        $data,
        int $statusCode = self::HTTP_OK,
        array $links = null,
        $meta = null,
        array $headers = []
    ) {
        return parent::getIdentifiersResponse($data, $statusCode, $links, $this->mergeDefaultMeta($meta), $headers);
    }

    /**
     * @inheritdoc
     */
    protected function createResponse(?string $content, int $statusCode, array $headers)
    {
        return new JsonApiResponse($content, $statusCode, $headers);
    }

    /**
     * @inheritdoc
     */
    protected function getEncoder(): EncoderInterface
    {
        return $this->encoder;
    }

    /**
     * @inheritdoc
     */
    protected function getUrlPrefix(): ?string
    {
        return $this->urlPrefix;
    }

    /**
     * @inheritdoc
     */
    protected function getEncodingParameters(): ?EncodingParametersInterface
    {
        return $this->parameters;
    }

    /**
     * @inheritdoc
     */
    protected function getSchemaContainer(): ?ContainerInterface
    {
        return $this->schemes;
    }

    /**
     * @inheritdoc
     */
    protected function getMediaType(): MediaTypeInterface
    {
        return $this->outputMediaType;
    }

    /**
     * @inheritdoc
     */
    protected function getResourceLocationUrl($resource): string
    {
        return parent::getResourceLocationUrl(
            $resource instanceof PaginatedDataInterface ? $resource->getData() : $resource
        );
    }

    /**
     * @return mixed|null
     */
    protected function getDefaultMeta()
    {
        return $this->defaultMeta;
    }

    /**
     * Merge with default meta if possible (if both are arrays).
     *
     * @param mixed $meta
     *
     * @return mixed|null
     */
    private function mergeDefaultMeta($meta)
    {
        $default = $this->getDefaultMeta();

        return $meta === null ?
            $default : (is_array($meta) === true && is_array($default) === true ? $meta + $default : $meta);
    }
}
