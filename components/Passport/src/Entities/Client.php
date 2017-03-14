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

use DateTimeInterface;
use Limoncello\Passport\Contracts\Entities\ClientInterface;

/**
 * @package Limoncello\Passport
 */
abstract class Client extends DatabaseItem implements ClientInterface
{
    /** Field name */
    const FIELD_ID = 'id_client';

    /** Field name */
    const FIELD_NAME = 'name';

    /** Field name */
    const FIELD_DESCRIPTION = 'description';

    /** Field name */
    const FIELD_CREDENTIALS = 'credentials';

    /** Field name */
    const FIELD_REDIRECT_URIS = 'redirect_uris';

    /** Field name */
    const FIELD_SCOPES = 'scopes';

    /** Field name */
    const FIELD_IS_CONFIDENTIAL = 'is_confidential';

    /** Field name */
    const FIELD_IS_USE_DEFAULT_SCOPE = 'is_use_default_scope';

    /** Field name */
    const FIELD_IS_SCOPE_EXCESS_ALLOWED = 'is_scope_excess_allowed';

    /** Field name */
    const FIELD_IS_CODE_GRANT_ENABLED = 'is_code_grant_enabled';

    /** Field name */
    const FIELD_IS_IMPLICIT_GRANT_ENABLED = 'is_implicit_grant_enabled';

    /** Field name */
    const FIELD_IS_PASSWORD_GRANT_ENABLED = 'is_password_grant_enabled';

    /** Field name */
    const FIELD_IS_CLIENT_GRANT_ENABLED = 'is_client_grant_enabled';

    /** Field name */
    const FIELD_IS_REFRESH_GRANT_ENABLED = 'is_refresh_grant_enabled';

    /**
     * @var string
     */
    private $identifierField = '';

    /**
     * @var string|null
     */
    private $nameField = null;

    /**
     * @var string|null
     */
    private $descriptionField = null;

    /**
     * @var string|null
     */
    private $credentialsField = null;

    /**
     * @var string[]
     */
    private $redirectUriStrings;

    /**
     * @var string[]
     */
    private $scopeIdentifiers;

    /**
     * @var bool
     */
    private $isConfidentialField = false;

    /**
     * @var bool
     */
    private $isUseDefaultScopeField = false;

    /**
     * @var bool
     */
    private $isScopeExcessAllowedField = false;

    /**
     * @var bool
     */
    private $isCodeAuthEnabledField = false;

    /**
     * @var bool
     */
    private $isImplicitAuthEnabledField = false;

    /**
     * @var bool
     */
    private $isPasswordGrantEnabledField = false;

    /**
     * @var bool
     */
    private $isClientGrantEnabledField = false;

    /**
     * @var bool
     */
    private $isRefreshGrantEnabledField = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ($this->hasDynamicProperty(static::FIELD_ID) === true) {
            $this
                ->setIdentifier($this->{static::FIELD_ID})
                ->setName($this->{static::FIELD_NAME})
                ->setDescription($this->{static::FIELD_DESCRIPTION})
                ->setCredentials($this->{static::FIELD_CREDENTIALS});
            $this
                ->parseIsConfidential($this->{static::FIELD_IS_CONFIDENTIAL})
                ->parseIsUseDefaultScope($this->{static::FIELD_IS_USE_DEFAULT_SCOPE})
                ->parseIsScopeExcessAllowed($this->{static::FIELD_IS_SCOPE_EXCESS_ALLOWED})
                ->parseIsCodeAuthEnabled($this->{static::FIELD_IS_CODE_GRANT_ENABLED})
                ->parseIsImplicitAuthEnabled($this->{static::FIELD_IS_IMPLICIT_GRANT_ENABLED})
                ->parseIsPasswordGrantEnabled($this->{static::FIELD_IS_PASSWORD_GRANT_ENABLED})
                ->parseIsClientGrantEnabled($this->{static::FIELD_IS_CLIENT_GRANT_ENABLED})
                ->parseIsRefreshGrantEnabled($this->{static::FIELD_IS_REFRESH_GRANT_ENABLED});
        } else {
            $this->
                setScopeIdentifiers([])->setRedirectUriStrings([]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return $this->identifierField;
    }

    /**
     * @inheritdoc
     */
    public function setIdentifier(string $identifier): ClientInterface
    {
        $this->identifierField = $identifier;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->nameField;
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): ClientInterface
    {
        $this->nameField = $name;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->descriptionField;
    }

    /**
     * @inheritdoc
     */
    public function setDescription(string $description = null): ClientInterface
    {
        $this->descriptionField = $description;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCredentials()
    {
        return $this->credentialsField;
    }

    /**
     * @inheritdoc
     */
    public function setCredentials(string $credentials = null): ClientInterface
    {
        $this->credentialsField = $credentials;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function hasCredentials(): bool
    {
        return empty($this->getCredentials()) === false;
    }

    /**
     * @inheritdoc
     */
    public function getRedirectUriStrings(): array
    {
        return $this->redirectUriStrings;
    }

    /**
     * @inheritdoc
     */
    public function setRedirectUriStrings(array $redirectUriStrings): ClientInterface
    {
        $this->redirectUriStrings = $redirectUriStrings;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getScopeIdentifiers(): array
    {
        return $this->scopeIdentifiers;
    }

    /**
     * @param string[] $scopeIdentifiers
     *
     * @return ClientInterface
     */
    public function setScopeIdentifiers(array $scopeIdentifiers): ClientInterface
    {
        $this->scopeIdentifiers = $scopeIdentifiers;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isConfidential(): bool
    {
        return $this->isConfidentialField;
    }

    /**
     * @inheritdoc
     */
    public function isPublic(): bool
    {
        return $this->isConfidential() === false;
    }

    /**
     * @inheritdoc
     */
    public function setConfidential(): ClientInterface
    {
        $this->isConfidentialField = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setPublic(): ClientInterface
    {
        $this->isConfidentialField = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isUseDefaultScopesOnEmptyRequest(): bool
    {
        return $this->isUseDefaultScopeField;
    }

    /**
     * @inheritdoc
     */
    public function useDefaultScopesOnEmptyRequest(): ClientInterface
    {
        $this->isUseDefaultScopeField = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function doNotUseDefaultScopesOnEmptyRequest(): ClientInterface
    {
        $this->isUseDefaultScopeField = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isScopeExcessAllowed(): bool
    {
        return $this->isScopeExcessAllowedField;
    }

    /**
     * @inheritdoc
     */
    public function enableScopeExcess(): ClientInterface
    {
        $this->isScopeExcessAllowedField = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function disableScopeExcess(): ClientInterface
    {
        $this->isScopeExcessAllowedField = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isCodeGrantEnabled(): bool
    {
        return $this->isCodeAuthEnabledField;
    }

    /**
     * @inheritdoc
     */
    public function enableCodeGrant(): ClientInterface
    {
        $this->isCodeAuthEnabledField = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function disableCodeGrant(): ClientInterface
    {
        $this->isCodeAuthEnabledField = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isImplicitGrantEnabled(): bool
    {
        return $this->isImplicitAuthEnabledField;
    }

    /**
     * @inheritdoc
     */
    public function enableImplicitGrant(): ClientInterface
    {
        $this->isImplicitAuthEnabledField = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function disableImplicitGrant(): ClientInterface
    {
        $this->isImplicitAuthEnabledField = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isPasswordGrantEnabled(): bool
    {
        return $this->isPasswordGrantEnabledField;
    }

    /**
     * @inheritdoc
     */
    public function enablePasswordGrant(): ClientInterface
    {
        $this->isPasswordGrantEnabledField = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function disablePasswordGrant(): ClientInterface
    {
        $this->isPasswordGrantEnabledField = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isClientGrantEnabled(): bool
    {
        return $this->isClientGrantEnabledField;
    }

    /**
     * @inheritdoc
     */
    public function enableClientGrant(): ClientInterface
    {
        $this->isClientGrantEnabledField = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function disableClientGrant(): ClientInterface
    {
        $this->isClientGrantEnabledField = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isRefreshGrantEnabled(): bool
    {
        return $this->isRefreshGrantEnabledField;
    }

    /**
     * @inheritdoc
     */
    public function enableRefreshGrant(): ClientInterface
    {
        $this->isRefreshGrantEnabledField = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function disableRefreshGrant(): ClientInterface
    {
        $this->isRefreshGrantEnabledField = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(DateTimeInterface $createdAt): ClientInterface
    {
        return $this->setCreatedAtImpl($createdAt);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(DateTimeInterface $createdAt): ClientInterface
    {
        return $this->setUpdatedAtImpl($createdAt);
    }

    /**
     * @param string $value
     *
     * @return Client
     */
    protected function parseIsConfidential(string $value): Client
    {
        $value === '1' ? $this->setConfidential() : $this->setPublic();

        return $this;
    }

    /**
     * @param string $value
     *
     * @return Client
     */
    protected function parseIsUseDefaultScope(string $value): Client
    {
        $value === '1' ? $this->useDefaultScopesOnEmptyRequest() : $this->doNotUseDefaultScopesOnEmptyRequest();

        return $this;
    }

    /**
     * @param string $value
     *
     * @return Client
     */
    protected function parseIsScopeExcessAllowed(string $value): Client
    {
        $value === '1' ? $this->enableScopeExcess() : $this->disableScopeExcess();

        return $this;
    }

    /**
     * @param string $value
     *
     * @return Client
     */
    protected function parseIsCodeAuthEnabled(string $value): Client
    {
        $value === '1' ? $this->enableCodeGrant() : $this->disableCodeGrant();

        return $this;
    }

    /**
     * @param string $value
     *
     * @return Client
     */
    protected function parseIsImplicitAuthEnabled(string $value): Client
    {
        $value === '1' ? $this->enableImplicitGrant() : $this->disableImplicitGrant();

        return $this;
    }

    /**
     * @param string $value
     *
     * @return Client
     */
    protected function parseIsPasswordGrantEnabled(string $value): Client
    {
        $value === '1' ? $this->enablePasswordGrant() : $this->disablePasswordGrant();

        return $this;
    }

    /**
     * @param string $value
     *
     * @return Client
     */
    protected function parseIsClientGrantEnabled(string $value): Client
    {
        $value === '1' ? $this->enableClientGrant() : $this->disableClientGrant();

        return $this;
    }

    /**
     * @param string $value
     *
     * @return Client
     */
    protected function parseIsRefreshGrantEnabled(string $value): Client
    {
        $value === '1' ? $this->enableRefreshGrant() : $this->disableRefreshGrant();

        return $this;
    }
}
