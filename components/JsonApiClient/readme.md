## Summary

[JSON API](http://jsonapi.org/) client side library.

The library helps to build queries to a JSON API server.

Supported features
- [Sparse Fieldsets](http://jsonapi.org/format/#fetching-sparse-fieldsets)
- [Filtering](http://jsonapi.org/format/#fetching-filtering)
- [Sorting](http://jsonapi.org/format/#fetching-sorting)
- [Inclusion of Related Resources](http://jsonapi.org/format/#fetching-includes)
- [Pagination](http://jsonapi.org/format/#fetching-pagination)

It can build queries for reading resource collections, individual resources by identifier and resource relationships.

Usage sample

```javascript
import { QueryBuilder } from '@limoncello-framework/json-api-client';

const query = (new QueryBuilder('comments'))
    .onlyFields({
        type: 'comments',
        fields: 'text'
    })
    .withFilters({
        field: 'id',
        operation: 'greater-than',
        parameters: '10'
    })
    .withSorts({
        field: 'title',
        isAscending: false
    })
    .withIncludes('post')
    .withPagination(50, 25)
    .index();

console.debug(query);
```

Output

> /comments?fields[comments]=text&filter[id][greater-than]=10&sort=-title&include=post&page[offset]=50&page[limit]=25

## Installation

```bash
$ npm install --save-dev @limoncello-framework/json-api-client
```

or

```bash
$ yarn add --dev @limoncello-framework/json-api-client
```

## Features

`QueryBuilder` has the following interface.

```typescript
interface QueryBuilderInterface {
    onlyFields(...fields: FieldParameterInterface[]): QueryBuilderInterface;

    withFilters(...filters: FilterParameterInterface[]): QueryBuilderInterface;

    withSorts(...sorts: SortParameterInterface[]): QueryBuilderInterface;

    withIncludes(...relationships: RelationshipName[]): QueryBuilderInterface;

    withPagination(offset: number, limit: number): QueryBuilderInterface;

    read(index: ResourceIdentity, relationship?: RelationshipName): string;

    index(): string;
}
```

Methods `onlyFields`, `withFilters`, `withSorts` and `withPagination` are fully shown in the example above and can accept 1 or more input parameters.

```javascript
builder
    .onlyFields({ ... }, { ... }, ...)
    .withFilters({ ... }, { ... }, ...)
    .withSorts({ ... }, { ... }, ...)
    .withIncludes('...', '...', ...)
    .index();
```

Parameter `operation` in `withFilters` method should have one the following values `equals`, `not-equals`, `less-than`, `less-or-equals`, `greater-than`, `greater-or-equals`, `like`, `not-like`, `in`, `not-in`, `is-null` or `not-null`.

Method `index` returns multiple resource requests such as

> /articles

and method `read` returns individual resource requests for resources themselves or their relationships

> /articles/1
>
> /articles/1/author

More usage samples could be found in `test` folder.

## Testing

- Clone the repository.
- Install dependencies with `npm install` or `yarn install`.
- Run tests with `npm run test` or `yarn test`.

## Questions

Feel free to open an issue marked as 'Question' in its title.
