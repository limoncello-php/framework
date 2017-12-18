<?php namespace Limoncello\Flute\Http\Traits;

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

use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Http\Responses;
use Limoncello\Flute\Package\FluteSettings as S;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\ResponsesInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;
use Neomerx\JsonApi\Http\Headers\SupportedExtensions;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Flute
 */
trait CreateResponsesTrait
{
    /**
     * @param ContainerInterface               $container
     * @param ServerRequestInterface           $request
     * @param EncodingParametersInterface|null $parameters
     *
     * @return ResponsesInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function createResponses(
        ContainerInterface $container,
        ServerRequestInterface $request,
        EncodingParametersInterface $parameters = null
    ): ResponsesInterface {
        /** @var EncoderInterface $encoder */
        $encoder = $container->get(EncoderInterface::class);
        $encoder->forOriginalUri($request->getUri());

        /** @var SettingsProviderInterface $provider */
        $provider = $container->get(SettingsProviderInterface::class);
        $settings = $provider->get(S::class);

        /** @var JsonSchemesInterface $jsonSchemes */
        $jsonSchemes = $container->get(JsonSchemesInterface::class);
        $responses   = new Responses(
            new MediaType(MediaTypeInterface::JSON_API_TYPE, MediaTypeInterface::JSON_API_SUB_TYPE),
            new SupportedExtensions(),
            $encoder,
            $jsonSchemes,
            $parameters,
            $settings[S::KEY_URI_PREFIX]
        );

        return $responses;
    }
}
