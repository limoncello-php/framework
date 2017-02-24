<?php namespace Limoncello\Passport\Entities;

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

use Limoncello\Passport\Contracts\Entities\RedirectUriInterface;
use Psr\Http\Message\UriInterface;
use Zend\Diactoros\Uri;

/**
 * @package Limoncello\Passport
 */
abstract class RedirectUri extends DatabaseItem implements RedirectUriInterface
{
    /** Field name */
    const FIELD_ID = 'id_redirect_uri';

    /** Field name */
    const FIELD_ID_CLIENT = Client::FIELD_ID;

    /** Field name */
    const FIELD_VALUE = 'uri';

    /**
     * @var int
     */
    private $identifierField;

    /**
     * @var string
     */
    private $clientIdentifierField;

    /**
     * @var string
     */
    private $valueField;

    /**
     * @var Uri|null
     */
    private $uriObject;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ($this->hasDynamicProperty(static::FIELD_ID) === true) {
            $this
                ->setIdentifier((int)$this->{static::FIELD_ID})
                ->setClientIdentifier($this->{static::FIELD_ID_CLIENT})
                ->setValue($this->{static::FIELD_VALUE});
        }
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): int
    {
        return $this->identifierField;
    }

    /**
     * @param int $identifier
     *
     * @return RedirectUri
     */
    public function setIdentifier(int $identifier): RedirectUri
    {
        $this->identifierField = $identifier;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getClientIdentifier(): string
    {
        return $this->clientIdentifierField;
    }

    /**
     * @param string $identifier
     *
     * @return RedirectUri
     */
    public function setClientIdentifier(string $identifier): RedirectUri
    {
        $this->clientIdentifierField = $identifier;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getValue(): string
    {
        return $this->valueField;
    }

    /**
     * @param string $uri
     *
     * @return RedirectUri
     */
    public function setValue(string $uri): RedirectUri
    {
        // TODO add fragment validation by spec

        $this->valueField = $uri;

        return $this;
    }

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface
    {
        if ($this->uriObject === null) {
            $this->uriObject = new Uri($this->getValue());
        }

        return $this->uriObject;
    }
}
