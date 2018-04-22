import 'mocha';
import { expect } from 'chai';
import { QueryBuilder } from '../src';

describe('Query builder', () => {
    it('should provide a switch to enable/disable URI encoding', () => {
        const builder = new QueryBuilder('comments');

        builder.enableEncodeUri();
        expect(builder.isUriEncodingEnabled()).to.equal(true);

        builder.disableEncodeUri();
        expect(builder.isUriEncodingEnabled()).to.equal(false);
    });

    it('should build index URLs without any extra parameters', () => {
        const builder = new QueryBuilder('comments');

        expect(builder.index()).to.equal(encodeURI('/comments'));
    });

    it('should build read URLs without any extra parameters (encoding on)', () => {
        const builder = new QueryBuilder('comments');

        expect(builder.isUriEncodingEnabled()).to.equal(true);

        const expectedUrl = encodeURI('/comments/123');
        const actualUrl = builder.read('123');

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should build read URLs without any extra parameters (encoding off)', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments/123');
        const actualUrl = builder.disableEncodeUri().read('123');

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should build read URLs without any extra parameters for a given relationship', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments/123/post');
        const actualUrl = builder.read('123', 'post');

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should build index URLs with one fields parameter', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments?fields[comments]=id,text');
        const actualUrl = builder
            .onlyFields({
                type: 'comments',
                fields: ['id', 'text']
            })
            .index();

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should build index URLs with many fields parameters', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments?fields[comments]=text&fields[posts]=title,body');
        const actualUrl = builder
            .onlyFields({
                type: 'comments',
                fields: 'text'
            }, {
                type: 'posts',
                fields: ['title', 'body']
            })
            .index();

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should build index URLs with many filter parameters', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = '/comments?filter[id][greater-than]=10&filter[id][less-than]=20&filter[code][in]=code1,code2&filter[deleted-at][is-null]';
        const actualUrl = builder
            .withFilters({
                field: 'id',
                operation: 'greater-than',
                parameters: '10'
            }, {
                field: 'id',
                operation: 'less-than',
                parameters: '20'
            }, {
                field: 'code',
                operation: 'in',
                parameters: ['code1', 'code2']
            }, {
                field: 'deleted-at',
                operation: 'is-null'
            })
            .disableEncodeUri()
            .index();

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should build index URLs with one sorting parameter', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments?sort=-id');
        const actualUrl = builder
            .withSorts({
                field: 'id',
                isAscending: false
            })
            .index();

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should build index URLs with many sorting parameters', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments?sort=-id,title');
        const actualUrl = builder
            .withSorts({
                field: 'id',
                isAscending: false
            }, {
                field: 'title',
                isAscending: true
            })
            .index();

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should build index URLs with one include parameter', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments?include=post');
        const actualUrl = builder
            .withIncludes('post')
            .index();

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should build index URLs with many include parameters', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments?include=post,likes');
        const actualUrl = builder
            .withIncludes('post', 'likes')
            .index();

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should build index URLs with pagination parameters', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments?page[offset]=50&page[limit]=25');
        const actualUrl = builder
            .withPagination(50, 25)
            .index();

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should build index URLs for zero offset pagination parameter', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments?page[offset]=0&page[limit]=25');
        const actualUrl = builder
            .withPagination(0, 25)
            .index();

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should ignore pagination parameters if offset is invalid', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments');
        const actualUrl = builder
            .withPagination(-1, 25)
            .index();

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should ignore pagination parameters if limit is invalid', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments');
        const actualUrl = builder
            .withPagination(50, 0)
            .index();

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should build index URLs with field, filter, sort, include and pagination parameters', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments?fields[comments]=text&filter[id][greater-than]=10&sort=-title&include=post&page[offset]=50&page[limit]=25');
        const actualUrl = builder
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

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should build index URLs with empty field, filter, sort and include parameters', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments');
        const actualUrl = builder
            .onlyFields()
            .withFilters()
            .withSorts()
            .withIncludes()
            .index();

        expect(actualUrl).to.equal(expectedUrl);
    });

    it('should ignore filter, sort, include and pagination but not field parameters for read method', () => {
        const builder = new QueryBuilder('comments');

        const expectedUrl = encodeURI('/comments/5/post?fields[comments]=text');
        const actualUrl = builder
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
            .read('5', 'post');

        expect(actualUrl).to.equal(expectedUrl);
    })
});
