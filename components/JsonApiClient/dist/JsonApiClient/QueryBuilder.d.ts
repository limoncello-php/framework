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
import { FieldParameterInterface } from '../Contracts/JsonApiClient/FieldParameterInterface';
import { FilterParameterInterface } from '../Contracts/JsonApiClient/FilterParameterInterface';
import { QueryBuilderInterface } from '../Contracts/JsonApiClient/QueryBuilderInterface';
import { SortParameterInterface } from '../Contracts/JsonApiClient/SortParameterInterface';
import { RelationshipName } from '../Contracts/JsonApi/RelationshipName';
import { ResourceIdentity } from '../Contracts/JsonApi/ResourceIdentity';
import { ResourceType } from '../Contracts/JsonApi/ResourceType';
export declare class QueryBuilder implements QueryBuilderInterface {
    private isEncodeUriEnabled;
    constructor(type: ResourceType);
    onlyFields(...fields: FieldParameterInterface[]): QueryBuilderInterface;
    withFilters(...filters: FilterParameterInterface[]): QueryBuilderInterface;
    combineFiltersWithAnd(): QueryBuilderInterface;
    combineFiltersWithOr(): QueryBuilderInterface;
    withSorts(...sorts: SortParameterInterface[]): QueryBuilderInterface;
    withIncludes(...relationships: RelationshipName[]): QueryBuilderInterface;
    withPagination(offset: number, limit: number): QueryBuilderInterface;
    enableEncodeUri(): QueryBuilderInterface;
    disableEncodeUri(): QueryBuilderInterface;
    isUriEncodingEnabled(): boolean;
    read(index: ResourceIdentity, relationship?: RelationshipName): string;
    index(): string;
}
