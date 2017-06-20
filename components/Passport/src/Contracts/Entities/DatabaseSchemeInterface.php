<?php namespace Limoncello\Passport\Contracts\Entities;

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

/**
 * @package Limoncello\Passport
 */
interface DatabaseSchemeInterface
{
    /**************************************************************************
     * OAuth Clients table and view.
     *************************************************************************/

    /**
     * @return string
     */
    public function getClientsViewScopesColumn(): string;

    /**
     * @return string
     */
    public function getClientsViewRedirectUrisColumn(): string;
    /**
     * @return string
     */
    public function getClientsView(): string;

    /**
     * @return string
     */
    public function getClientsTable(): string;

    /**
     * @return string
     */
    public function getClientsIdentityColumn(): string;

    /**
     * @return string
     */
    public function getClientsNameColumn(): string;

    /**
     * @return string
     */
    public function getClientsDescriptionColumn(): string;

    /**
     * @return string
     */
    public function getClientsCredentialsColumn(): string;

    /**
     * @return string
     */
    public function getClientsIsConfidentialColumn(): string;

    /**
     * @return string
     */
    public function getClientsIsScopeExcessAllowedColumn(): string;

    /**
     * @return string
     */
    public function getClientsIsUseDefaultScopeColumn(): string;

    /**
     * @return string
     */
    public function getClientsIsCodeGrantEnabledColumn(): string;

    /**
     * @return string
     */
    public function getClientsIsImplicitGrantEnabledColumn(): string;

    /**
     * @return string
     */
    public function getClientsIsPasswordGrantEnabledColumn(): string;

    /**
     * @return string
     */
    public function getClientsIsClientGrantEnabledColumn(): string;

    /**
     * @return string
     */
    public function getClientsIsRefreshGrantEnabledColumn(): string;

    /**
     * @return string
     */
    public function getClientsCreatedAtColumn(): string;

    /**
     * @return string
     */
    public function getClientsUpdatedAtColumn(): string;

    /**************************************************************************
     * OAuth Clients-Scopes table.
     *************************************************************************/

    /**
     * @return string
     */
    public function getClientsScopesIdentityColumn(): string;

    /**
     * @return string
     */
    public function getClientsScopesTable(): string;

    /**
     * @return string
     */
    public function getClientsScopesClientIdentityColumn(): string;

    /**
     * @return string
     */
    public function getClientsScopesScopeIdentityColumn(): string;

    /**************************************************************************
     * OAuth Redirect URIs table.
     *************************************************************************/

    /**
     * @return string
     */
    public function getRedirectUrisTable(): string;

    /**
     * @return string
     */
    public function getRedirectUrisIdentityColumn(): string;

    /**
     * @return string
     */
    public function getRedirectUrisClientIdentityColumn(): string;

    /**
     * @return string
     */
    public function getRedirectUrisValueColumn(): string;

    /**
     * @return string
     */
    public function getRedirectUrisCreatedAtColumn(): string;

    /**
     * @return string
     */
    public function getRedirectUrisUpdatedAtColumn(): string;

    /**************************************************************************
     * OAuth Scopes table.
     *************************************************************************/

    /**
     * @return string
     */
    public function getScopesTable(): string;

    /**
     * @return string
     */
    public function getScopesIdentityColumn(): string;

    /**
     * @return string
     */
    public function getScopesDescriptionColumn(): string;

    /**
     * @return string
     */
    public function getScopesCreatedAtColumn(): string;

    /**
     * @return string
     */
    public function getScopesUpdatedAtColumn(): string;

    /**************************************************************************
     * OAuth Tokens table and view.
     *************************************************************************/

    /**
     * @return string
     */
    public function getTokensView(): string;

    /**
     * @return string
     */
    public function getTokensViewScopesColumn(): string;

    /**
     * @return string
     */
    public function getTokensTable(): string;

    /**
     * @return string
     */
    public function getTokensIdentityColumn(): string;

    /**
     * @return string
     */
    public function getTokensIsEnabledColumn(): string;

    /**
     * @return string
     */
    public function getTokensIsScopeModified(): string;

    /**
     * @return string
     */
    public function getTokensClientIdentityColumn(): string;

    /**
     * @return string
     */
    public function getTokensUserIdentityColumn(): string;

    /**
     * @return string
     */
    public function getTokensRedirectUriColumn(): string;

    /**
     * @return string
     */
    public function getTokensCodeColumn(): string;

    /**
     * @return string
     */
    public function getTokensValueColumn(): string;

    /**
     * @return string
     */
    public function getTokensTypeColumn(): string;

    /**
     * @return string
     */
    public function getTokensRefreshColumn(): string;

    /**
     * @return string
     */
    public function getTokensCodeCreatedAtColumn(): string;

    /**
     * @return string
     */
    public function getTokensValueCreatedAtColumn(): string;

    /**
     * @return string
     */
    public function getTokensRefreshCreatedAtColumn(): string;

    /**************************************************************************
     * OAuth Tokens-Scopes table.
     *************************************************************************/

    /**
     * @return string
     */
    public function getTokensScopesTable(): string;

    /**
     * @return string
     */
    public function getTokensScopesIdentityColumn(): string;

    /**
     * @return string
     */
    public function getTokensScopesTokenIdentityColumn(): string;

    /**
     * @return string
     */
    public function getTokensScopesScopeIdentityColumn(): string;

    /**************************************************************************
     * Users table and view.
     *************************************************************************/

    /**
     * @return string|null
     */
    public function getUsersView();

    /**
     * @return string|null
     */
    public function getUsersTable();

    /**
     * @return string|null
     */
    public function getUsersIdentityColumn();

    /**************************************************************************
     * Passport view.
     *************************************************************************/

    /**
     * @return string|null
     */
    public function getPassportView();
}
