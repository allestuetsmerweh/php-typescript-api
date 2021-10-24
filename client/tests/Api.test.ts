/* eslint-env jasmine */

import fetch from 'unfetch';
import {Api} from '../lib/Api';

type UnfetchFetchFunction = typeof fetch;

type FakeApiEndpoint = 'fake1'|'fake2';
type FakeApiRequests = {
    'fake1': string,
    'fake2': {arg1: number|null, arg2: string},
};
type FakeApiResponses = {
    'fake1': string[],
    'fake2': string,
};

class FakeApi extends Api<FakeApiEndpoint, FakeApiRequests, FakeApiResponses> {
    public baseUrl = 'http://unit-test/fake_api_server.php';

    public testOnlyMockFetchFunction(fakeFetchFunction: UnfetchFetchFunction) {
        this.fetchFunction = fakeFetchFunction;
    }
}

describe('Api', () => {
    it('successfully calls API', async () => {
        const api = new FakeApi();
        const fakeFetch = jest.fn();
        api.testOnlyMockFetchFunction(fakeFetch);
        const fakeUnfetchResponse = {json: () => ['test', '1234']};
        fakeFetch.mockReturnValue(Promise.resolve(fakeUnfetchResponse));

        const response = await api.call('fake1', 'test-1234');
        expect(fakeFetch).toHaveBeenCalledWith(
            'http://unit-test/fake_api_server.php/fake1',
            {
                body: '"test-1234"',
                headers: {'Content-Type': 'application/json'},
                method: 'POST',
            },
        );
        expect(response).toEqual(['test', '1234']);
    });

    it('handles API errors', async () => {
        const api = new FakeApi();
        const fakeFetch = jest.fn();
        api.testOnlyMockFetchFunction(fakeFetch);
        fakeFetch.mockReturnValue(Promise.reject(new Error('test-error')));

        try {
            await api.call('fake1', 'test-1234');
        } catch (err: unknown) {
            if (!(err instanceof Error)) {
                throw new Error(`Error was not an error: ${err}`);
            }
            expect(err.message).toBe('test-error');
        }
    });
});
