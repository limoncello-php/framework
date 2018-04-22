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

import { FieldParameterInterface } from '../Contracts/JsonApiClient/FieldParameterInterface';
import { FilterParameterInterface } from '../Contracts/JsonApiClient/FilterParameterInterface';
import { QueryBuilderInterface } from '../Contracts/JsonApiClient/QueryBuilderInterface';
import { SortParameterInterface } from '../Contracts/JsonApiClient/SortParameterInterface';
import { RelationshipName } from '../Contracts/JsonApi/RelationshipName';
import { ResourceIdentity } from '../Contracts/JsonApi/ResourceIdentity';
import { ResourceType } from '../Contracts/JsonApi/ResourceType';

export class QueryBuilder implements QueryBuilderInterface {
    /**
     * @internal
     */
    private type: ResourceType;

    /**
     * @internal
     */
    private fields: FieldParameterInterface[] | undefined;

    /**
     * @internal
     */
    private filters: FilterParameterInterface[] | undefined;

    /**
     * @internal
     */
    private sorts: SortParameterInterface[] | undefined;

    /**
     * @internal
     */
    private includes: RelationshipName[] | undefined;

    /**
     * @internal
     */
    private offset: number | undefined;

    /**
     * @internal
     */
    private limit: number | undefined;

    private isEncodeUriEnabled: boolean;

    constructor(type: ResourceType) {
        this.isEncodeUriEnabled = true;
        this.type = type;
    }

    public onlyFields(...fields: FieldParameterInterface[]): QueryBuilderInterface {
        this.fields = fields;

        return this;
    }

    public withFilters(...filters: FilterParameterInterface[]): QueryBuilderInterface {
        this.filters = filters;

        return this;
    }

    public withSorts(...sorts: SortParameterInterface[]): QueryBuilderInterface {
        this.sorts = sorts;

        return this;
    }

    public withIncludes(...relationships: RelationshipName[]): QueryBuilderInterface {
        this.includes = relationships;

        return this;
    }

    public withPagination(offset: number, limit: number): QueryBuilderInterface {
        offset = Math.max(-1, Math.floor(offset));
        limit = Math.max(0, Math.floor(limit));

        if (offset >= 0 && limit > 0) {
            this.offset = offset;
            this.limit = limit;
        } else {
            this.offset = undefined;
            this.limit = undefined;
        }

        return this;
    }

    public enableEncodeUri(): QueryBuilderInterface {
        this.isEncodeUriEnabled = true;

        return this;
    }

    public disableEncodeUri(): QueryBuilderInterface {
        this.isEncodeUriEnabled = false;

        return this;
    }

    public isUriEncodingEnabled(): boolean {
        return this.isEncodeUriEnabled;
    }

    public read(index: ResourceIdentity, relationship?: RelationshipName): string {
        const relationshipTail = relationship === undefined ? `/${index}` : `/${index}/${relationship}`;
        const result = `/${this.type}${relationshipTail}${this.buildParameters(false)}`;

        return this.isUriEncodingEnabled() === true ? encodeURI(result) : result;
    }

    public index(): string {
        const result = `/${this.type}${this.buildParameters(true)}`;

        return this.isUriEncodingEnabled() === true ? encodeURI(result) : result;
    }

    /**
     * @internal
     */
    private buildParameters(isIncludeNonFields: boolean): string {
        let params = null;

        // add field params to get URL like '/articles?include=author&fields[articles]=title,body&fields[people]=name'
        // see http://jsonapi.org/format/#fetching-sparse-fieldsets
        if (this.fields !== undefined && this.fields.length > 0) {
            let fieldsResult = '';
            for (let field of this.fields) {
                const curResult = `fields[${field.type}]=${QueryBuilder.separateByComma(field.fields)}`;
                fieldsResult = fieldsResult.length === 0 ? curResult : `${fieldsResult}&${curResult}`;
            }
            params = fieldsResult;
        }

        // add filter parameters to get URL like 'filter[id][greater-than]=10&filter[id][less-than]=20&filter[title][like]=%Typ%'
        // note: the spec do not specify format for filters http://jsonapi.org/format/#fetching-filtering
        if (isIncludeNonFields === true && this.filters !== undefined && this.filters.length > 0) {
            let filtersResult = '';
            for (let filter of this.filters) {
                const params = filter.parameters;
                const curResult = params === undefined ?
                    `filter[${filter.field}][${filter.operation}]` :
                    `filter[${filter.field}][${filter.operation}]=${QueryBuilder.separateByComma(params)}`;
                filtersResult = filtersResult.length === 0 ? curResult : `${filtersResult}&${curResult}`;
            }
            params = params === null ? filtersResult : `${params}&${filtersResult}`;
        }

        // add sorts to get URL like '/articles?sort=-created,title'
        // see http://jsonapi.org/format/#fetching-sorting
        if (isIncludeNonFields === true && this.sorts !== undefined && this.sorts.length > 0) {
            let sortsList = '';
            for (let sort of this.sorts) {
                const sortParam = `${sort.isAscending === true ? '' : '-'}${sort.field}`;
                sortsList = sortsList.length > 0 ? `${sortsList},${sortParam}` : sortParam;
            }
            const sortsResult = `sort=${sortsList}`;
            params = params === null ? sortsResult : `${params}&${sortsResult}`;
        }

        // add includes to get URL like '/articles/1?include=author,comments.author'
        // see http://jsonapi.org/format/#fetching-includes
        if (isIncludeNonFields === true && this.includes !== undefined && this.includes.length > 0) {
            const includesResult = `include=${QueryBuilder.separateByComma(this.includes)}`;
            params = params === null ? includesResult : `${params}&${includesResult}`;
        }

        // add pagination to get URL like '/articles?page[offset]=50&page[limit]=25'
        // note: the spec do not strictly define pagination parameters
        if (isIncludeNonFields === true && this.offset !== undefined && this.limit !== undefined) {
            const paginationResult = `page[offset]=${this.offset}&page[limit]=${this.limit}`;
            params = params === null ? paginationResult : `${params}&${paginationResult}`;
        }

        return params === null ? '' : `?${params}`;
    }

    /**
     * @internal
     */
    private static separateByComma(values: string | string[]): string {
        return Array.isArray(values) === true ? (<string[]>values).join(',') : `${<string>values}`;
    }
}
