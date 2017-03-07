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

use DateTimeImmutable;
use Limoncello\Passport\Contracts\Entities\TokenInterface;

/**
 * @package Limoncello\Passport
 */
abstract class Token implements TokenInterface
{
    /**
     * @return string
     */
    abstract protected function getListSeparator(): string;

    /**
     * @return string
     */
    abstract protected function getDbDateFormat(): string;

    /** Field name */
    const FIELD_ID = 'id_token';

    /** Field name */
    const FIELD_ID_CLIENT = Client::FIELD_ID;

    /** Field name */
    const FIELD_ID_USER = 'id_user';

    /** Field name */
    const FIELD_TOKEN_SCOPE_LIST = 'token_scope_list';

    /** Field name */
    const FIELD_IS_SCOPE_MODIFIED = 'is_scope_modified';

    /** Field name */
    const FIELD_IS_ENABLED = 'is_enabled';

    /** Field name */
    const FIELD_REDIRECT_URI = 'redirect_uri';

    /** Field name */
    const FIELD_CODE = 'code';

    /** Field name */
    const FIELD_VALUE = 'value';

    /** Field name */
    const FIELD_TYPE = 'type';

    /** Field name */
    const FIELD_REFRESH = 'refresh';

    /** Field name */
    const FIELD_CODE_CREATED_AT = 'code_created_at';

    /** Field name */
    const FIELD_VALUE_CREATED_AT = 'value_created_at';

    /** Field name */
    const FIELD_REFRESH_CREATED_AT = 'refresh_created_at';

    /**
     * @var int
     */
    private $identifierField;

    /**
     * @var string
     */
    private $clientIdentifierField;

    /**
     * @var int
     */
    private $userIdentifierField;

    /**
     * @var string[]
     */
    private $tokenScopeStrings = [];

    /**
     * @var bool
     */
    private $isScopeModified = false;

    /**
     * @var bool
     */
    private $isEnabled = false;

    /**
     * @var string|null
     */
    private $redirectUriString = null;

    /**
     * @var string|null
     */
    private $codeField = null;

    /**
     * @var string|null
     */
    private $valueField = null;

    /**
     * @var string|null
     */
    private $typeField = null;

    /**
     * @var string|null
     */
    private $refreshValueField = null;

    /**
     * @var DateTimeImmutable|null
     */
    private $codeCreatedAtField = null;

    /**
     * @var DateTimeImmutable|null
     */
    private $valueCreatedAtField = null;

    /**
     * @var DateTimeImmutable|null
     */
    private $refreshCreatedAtField = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ($this->hasDynamicProperty(static::FIELD_ID) === true) {
            $this
                ->setIdentifier((int)$this->{static::FIELD_ID})
                ->setClientIdentifier($this->{static::FIELD_ID_CLIENT})
                ->setUserIdentifier((int)$this->{static::FIELD_ID_USER})
                ->setRedirectUriString($this->{static::FIELD_REDIRECT_URI})
                ->setCode($this->{static::FIELD_CODE})
                ->setType($this->{static::FIELD_TYPE})
                ->setValue($this->{static::FIELD_VALUE})
                ->setRefreshValue($this->{static::FIELD_REFRESH})
                ->parseTokenScopeList(
                    $this->hasDynamicProperty(static::FIELD_TOKEN_SCOPE_LIST) === true ?
                        $this->{static::FIELD_TOKEN_SCOPE_LIST} : ''
                )->parseIsScopeModified($this->{static::FIELD_IS_SCOPE_MODIFIED})
                ->parseIsEnabled($this->{static::FIELD_IS_ENABLED});
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
     * @return Token
     */
    public function setIdentifier(int $identifier): Token
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
     * @return Token
     */
    public function setClientIdentifier(string $identifier): Token
    {
        $this->clientIdentifierField = $identifier;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUserIdentifier()
    {
        return $this->userIdentifierField;
    }

    /**
     * @param int $identifier
     *
     * @return Token
     */
    public function setUserIdentifier(int $identifier): Token
    {
        $this->userIdentifierField = $identifier;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getScopeIdentifiers(): array
    {
        return $this->tokenScopeStrings;
    }

    /**
     * @param string $uriList
     *
     * @return Token
     */
    public function parseTokenScopeList(string $uriList): Token
    {
        return $this->setTokenScopeStrings(
            empty($uriList) === true ? [] : explode($this->getListSeparator(), $uriList)
        );
    }

    /**
     * @param string[] $tokenScopeStrings
     *
     * @return Token
     */
    public function setTokenScopeStrings(array $tokenScopeStrings): Token
    {
        $this->tokenScopeStrings = $tokenScopeStrings;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRedirectUriString()
    {
        return $this->redirectUriString;
    }

    /**
     * @param string|null $uri
     *
     * @return Token
     */
    public function setRedirectUriString(string $uri = null): Token
    {
        $this->redirectUriString = $uri;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isScopeModified(): bool
    {
        return $this->isScopeModified;
    }

    /**
     * @param string $value
     *
     * @return Token
     */
    protected function parseIsScopeModified(string $value): Token
    {
        $value === '1' ? $this->setScopeModified() : $this->setScopeUnmodified();

        return $this;
    }

    /**
     * @return Token
     */
    public function setScopeModified(): Token
    {
        $this->isScopeModified = true;

        return $this;
    }

    /**
     * @return Token
     */
    public function setScopeUnmodified(): Token
    {
        $this->isScopeModified = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * @param string $value
     *
     * @return Token
     */
    protected function parseIsEnabled(string $value): Token
    {
        $value === '1' ? $this->setEnabled() : $this->setDisabled();

        return $this;
    }

    /**
     * @return Token
     */
    public function setEnabled(): Token
    {
        $this->isEnabled = true;

        return $this;
    }

    /**
     * @return Token
     */
    public function setDisabled(): Token
    {
        $this->isEnabled = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCode()
    {
        return $this->codeField;
    }

    /**
     * @param string|null $code
     *
     * @return Token
     */
    public function setCode(string $code = null): Token
    {
        $this->codeField = $code;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getValue()
    {
        return $this->valueField;
    }

    /**
     * @param string|null $value
     *
     * @return Token
     */
    public function setValue(string $value = null): Token
    {
        $this->valueField = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->typeField;
    }

    /**
     * @param string|null $type
     *
     * @return Token
     */
    public function setType(string $type = null): Token
    {
        $this->typeField = $type;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRefreshValue()
    {
        return $this->refreshValueField;
    }

    /**
     * @param string|null $refreshValue
     *
     * @return Token
     */
    public function setRefreshValue(string $refreshValue = null): Token
    {
        $this->refreshValueField = $refreshValue;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCodeCreatedAt()
    {
        if ($this->codeCreatedAtField === null && ($codeCreatedAt = $this->{static::FIELD_CODE_CREATED_AT}) !== null) {
            $this->codeCreatedAtField = $this->parseDateTime($codeCreatedAt);
        }

        return $this->codeCreatedAtField;
    }

    /**
     * @param DateTimeImmutable $codeCreatedAt
     *
     * @return Token
     */
    public function setCodeCreatedAt(DateTimeImmutable $codeCreatedAt): Token
    {
        $this->codeCreatedAtField = $codeCreatedAt;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getValueCreatedAt()
    {
        if ($this->valueCreatedAtField === null &&
            ($tokenCreatedAt = $this->{static::FIELD_VALUE_CREATED_AT}) !== null
        ) {
            $this->valueCreatedAtField = $this->parseDateTime($tokenCreatedAt);
        }

        return $this->valueCreatedAtField;
    }

    /**
     * @param DateTimeImmutable $valueCreatedAt
     *
     * @return Token
     */
    public function setValueCreatedAt(DateTimeImmutable $valueCreatedAt): Token
    {
        $this->valueCreatedAtField = $valueCreatedAt;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRefreshCreatedAt()
    {
        if ($this->refreshCreatedAtField === null &&
            ($tokenCreatedAt = $this->{static::FIELD_VALUE_CREATED_AT}) !== null
        ) {
            $this->refreshCreatedAtField = $this->parseDateTime($tokenCreatedAt);
        }

        return $this->refreshCreatedAtField;
    }

    /**
     * @param DateTimeImmutable $refreshCreatedAt
     *
     * @return Token
     */
    public function setRefreshCreatedAt(DateTimeImmutable $refreshCreatedAt): Token
    {
        $this->refreshCreatedAtField = $refreshCreatedAt;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function hasBeenUsedEarlier(): bool
    {
        return $this->getValueCreatedAt() !== null;
    }

    /**
     * @param string $createdAt
     *
     * @return DateTimeImmutable
     */
    protected function parseDateTime(string $createdAt): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat($this->getDbDateFormat(), $createdAt);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function hasDynamicProperty(string $name): bool
    {
        return property_exists($this, $name);
    }
}
