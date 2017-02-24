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

use Limoncello\Passport\Contracts\Entities\ClientInterface;

/**
 * @package Limoncello\Passport
 */
abstract class Client extends DatabaseItem implements ClientInterface
{
    /**
     * @return string
     */
    abstract protected function getListSeparator(): string;

    /** Field name */
    const FIELD_ID = 'id_client';

    /** Field name */
    const FIELD_NAME = 'name';

    /** Field name */
    const FIELD_DESCRIPTION = 'description';

    /** Field name */
    const FIELD_CREDENTIALS = 'credentials';

    /** Field name */
    const FIELD_REDIRECT_URI_LIST = 'redirect_uri_list';

    /** Field name */
    const FIELD_SCOPE_LIST = 'scope_list';

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

    /**
     * @var string
     */
    private $identifierField;

    /**
     * @var string
     */
    private $nameField;

    /**
     * @var string|null
     */
    private $descriptionField;

    /**
     * @var string|null
     */
    private $credentialsField;

    /**
     * @var string[]
     */
    private $redirectUriStrings;

    /**
     * @var string[]
     */
    private $scopeStrings;

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
     * Constructor.
     */
    public function __construct()
    {
        if ($this->hasDynamicProperty(static::FIELD_ID) === true) {
            $this
                ->setIdentifier($this->{static::FIELD_ID})
                ->setName($this->{static::FIELD_NAME})
                ->setDescription($this->{static::FIELD_DESCRIPTION})
                ->setCredentials($this->{static::FIELD_CREDENTIALS})
                ->parseIsConfidential($this->{static::FIELD_IS_CONFIDENTIAL})
                ->parseIsUseDefaultScope($this->{static::FIELD_IS_USE_DEFAULT_SCOPE})
                ->parseIsScopeExcessAllowed($this->{static::FIELD_IS_SCOPE_EXCESS_ALLOWED})
                ->parseIsCodeAuthEnabled($this->{static::FIELD_IS_CODE_GRANT_ENABLED})
                ->parseIsImplicitAuthEnabled($this->{static::FIELD_IS_IMPLICIT_GRANT_ENABLED})
                ->parseIsPasswordGrantEnabled($this->{static::FIELD_IS_PASSWORD_GRANT_ENABLED})
                ->parseIsClientGrantEnabled($this->{static::FIELD_IS_CLIENT_GRANT_ENABLED})
                ->parseScopeList(
                    $this->hasDynamicProperty(static::FIELD_SCOPE_LIST) === true ?
                        $this->{static::FIELD_SCOPE_LIST} : ''
                )->parseRedirectUriList(
                    $this->hasDynamicProperty(static::FIELD_REDIRECT_URI_LIST) === true ?
                        $this->{static::FIELD_REDIRECT_URI_LIST} : ''
                );
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
     * @param string $identifier
     *
     * @return Client
     */
    public function setIdentifier(string $identifier): Client
    {
        $this->identifierField = $identifier;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->nameField;
    }

    /**
     * @param string $name
     *
     * @return Client
     */
    public function setName(string $name): Client
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
     * @param string|null $description
     *
     * @return Client
     */
    public function setDescription(string $description = null): Client
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
     * @param string $credentials
     *
     * @return Client
     */
    public function setCredentials(string $credentials = null): Client
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
     * @param string $uriList
     *
     * @return Client
     */
    public function parseRedirectUriList(string $uriList): Client
    {
        return $this->setRedirectUriStrings(
            empty($uriList) === true ? [] : explode($this->getListSeparator(), $uriList)
        );
    }

    /**
     * @param string[] $redirectUriStrings
     *
     * @return Client
     */
    public function setRedirectUriStrings(array $redirectUriStrings): Client
    {
        $this->redirectUriStrings = $redirectUriStrings;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getScopeStrings(): array
    {
        return $this->scopeStrings;
    }

    /**
     * @param string $uriList
     *
     * @return Client
     */
    public function parseScopeList(string $uriList): Client
    {
        return $this->setScopeStrings(
            empty($uriList) === true ? [] : explode($this->getListSeparator(), $uriList)
        );
    }

    /**
     * @param string[] $scopeStrings
     *
     * @return Client
     */
    public function setScopeStrings(array $scopeStrings): Client
    {
        $this->scopeStrings = $scopeStrings;

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
     * @return Client
     */
    public function setConfidential(): Client
    {
        $this->isConfidentialField = true;

        return $this;
    }

    /**
     * @return Client
     */
    public function setPublic(): Client
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
     * @return Client
     */
    public function useDefaultScopesOnEmptyRequest(): Client
    {
        $this->isUseDefaultScopeField = true;

        return $this;
    }

    /**
     * @return Client
     */
    public function doNotUseDefaultScopesOnEmptyRequest(): Client
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
     * @return Client
     */
    public function enableScopeExcess(): Client
    {
        $this->isScopeExcessAllowedField = true;

        return $this;
    }

    /**
     * @return Client
     */
    public function disableScopeExcess(): Client
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
     * @return Client
     */
    public function enableCodeAuthorization(): Client
    {
        $this->isCodeAuthEnabledField = true;

        return $this;
    }

    /**
     * @return Client
     */
    public function disableCodeAuthorization(): Client
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
     * @return Client
     */
    public function enableImplicitGrant(): Client
    {
        $this->isImplicitAuthEnabledField = true;

        return $this;
    }

    /**
     * @return Client
     */
    public function disableImplicitGrant(): Client
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
     * @return Client
     */
    public function enablePasswordGrant(): Client
    {
        $this->isPasswordGrantEnabledField = true;

        return $this;
    }

    /**
     * @return Client
     */
    public function disablePasswordGrant(): Client
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
     * @return Client
     */
    public function enableClientGrant(): Client
    {
        $this->isClientGrantEnabledField = true;

        return $this;
    }

    /**
     * @return Client
     */
    public function disableClientGrant(): Client
    {
        $this->isClientGrantEnabledField = false;

        return $this;
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
        $value === '1' ? $this->enableCodeAuthorization() : $this->disableCodeAuthorization();

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
}
