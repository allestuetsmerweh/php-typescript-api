import fetch from 'unfetch';
import {Api} from '../src/Api';
import {ApiError} from '../src/ApiError';

type UnfetchFetchFunction = typeof fetch;

type FakeApiEndpoint = 'fake1' | 'fake2';
type FakeApiRequests = {
    'fake1': string,
    'fake2': {arg1: number | null, arg2: string},
};
type FakeApiResponses = {
    'fake1': string[],
    'fake2': string,
};

class FakeApi extends Api<FakeApiEndpoint, FakeApiRequests, FakeApiResponses> {
    public baseUrl = '/fake_api_server.php';

    public testOnlyMockFetchFunction(fakeFetchFunction: UnfetchFetchFunction) {
        this.fetchFunction = fakeFetchFunction;
    }
}

describe('Api', () => {
    describe('call', () => {
        it('returns API response', async () => {
            const api = new FakeApi();
            const fakeFetch = jest.fn();
            api.testOnlyMockFetchFunction(fakeFetch);
            const responseJson = ['test', '1234'];
            const fakeUnfetchResponse = {
                json: () => Promise.resolve(responseJson),
                ok: true,
                text: () => Promise.resolve(JSON.stringify(responseJson)),
            };
            fakeFetch.mockReturnValue(Promise.resolve(fakeUnfetchResponse));

            const response = await api.call('fake1', 'test-1234');
            expect(fakeFetch).toHaveBeenCalledWith(
                '/fake_api_server.php/fake1',
                {
                    body: '"test-1234"',
                    headers: {'Content-Type': 'application/json'},
                    method: 'POST',
                },
            );
            expect(response).toEqual(['test', '1234']);
        });

        it('returns API errors', async () => {
            const api = new FakeApi();
            const fakeFetch = jest.fn();
            api.testOnlyMockFetchFunction(fakeFetch);
            const responseJson = {
                status: 400,
                error: {'.': ['Not gonna do that :/']},
                message: 'Validation Error',
            };
            const fakeUnfetchResponse = {
                json: () => Promise.resolve(responseJson),
                ok: false,
                text: () => Promise.resolve(JSON.stringify(responseJson)),
            };
            fakeFetch.mockReturnValue(Promise.resolve(fakeUnfetchResponse));

            try {
                await api.call('fake1', 'test-1234');
            } catch (err: unknown) {
                if (!(err instanceof Error)) {
                    fail(`Error was not an error: ${err}`);
                }
                expect(fakeFetch).toHaveBeenCalledWith(
                    '/fake_api_server.php/fake1',
                    {
                        body: '"test-1234"',
                        headers: {'Content-Type': 'application/json'},
                        method: 'POST',
                    },
                );
                expect(err.message).toBe('Validation Error');
            }
        });

        it('handles missing error', async () => {
            const api = new FakeApi();
            const fakeFetch = jest.fn();
            api.testOnlyMockFetchFunction(fakeFetch);
            const responseJson = {};
            const fakeUnfetchResponse = {
                json: () => Promise.resolve(responseJson),
                ok: false,
                text: () => Promise.resolve(JSON.stringify(responseJson)),
            };
            fakeFetch.mockReturnValue(Promise.resolve(fakeUnfetchResponse));

            try {
                await api.call('fake1', 'test-1234');
            } catch (err: unknown) {
                if (!(err instanceof Error)) {
                    fail(`Error was not an error: ${err}`);
                }
                expect(fakeFetch).toHaveBeenCalledWith(
                    '/fake_api_server.php/fake1',
                    {
                        body: '"test-1234"',
                        headers: {'Content-Type': 'application/json'},
                        method: 'POST',
                    },
                );
                expect(err.message).toBe(
                    'Ein Fehler ist aufgetreten. Bitte später nochmals versuchen.',
                );
            }
        });

        it('handles connection errors', async () => {
            const api = new FakeApi();
            const fakeFetch = jest.fn();
            api.testOnlyMockFetchFunction(fakeFetch);
            fakeFetch.mockReturnValue(Promise.reject(new Error('test-error')));

            try {
                await api.call('fake1', 'test-1234');
            } catch (err: unknown) {
                if (!(err instanceof Error)) {
                    fail(`Error was not an error: ${err}`);
                }
                expect(err.message).toBe('test-error');
            }
        });
    });

    describe('getApiErrorFromResponseText', () => {
        let api: FakeApi;

        beforeEach(() => {
            api = new FakeApi();
        });

        it('works when there is no reponse text', () => {
            expect(api.getApiErrorFromResponseText(undefined)).toEqual(undefined);
            expect(api.getApiErrorFromResponseText('')).toEqual(undefined);
        });

        it('works for invalid JSON', () => {
            expect(api.getApiErrorFromResponseText('invalid json')).toEqual(undefined);
        });

        it('works for non-ApiError JSON', () => {
            expect(api.getApiErrorFromResponseText(JSON.stringify(null))).toEqual(undefined);
            expect(api.getApiErrorFromResponseText(JSON.stringify({}))).toEqual(undefined);
            expect(api.getApiErrorFromResponseText(JSON.stringify({
                error: {},
            }))).toEqual(undefined);
            expect(api.getApiErrorFromResponseText(JSON.stringify({
                error: {type: 'invalid'},
            }))).toEqual(undefined);
        });

        it('works without status', () => {
            expect(api.getApiErrorFromResponseText(JSON.stringify({
                error: {'field1': ['testError']},
                message: 'testMessage',
            }))).toEqual(undefined);
        });

        it('works without message', () => {
            expect(api.getApiErrorFromResponseText(JSON.stringify({
                status: 400,
                error: ['test'],
            }))).toEqual(undefined);
        });

        it('works with empty validation errors', () => {
            expect(api.getApiErrorFromResponseText(JSON.stringify({
                status: 400,
                error: {},
                message: 'test',
            }))).toEqual(new ApiError(400, 'test', {}));
        });

        it('works without validation errors', () => {
            expect(api.getApiErrorFromResponseText(JSON.stringify({
                status: 400,
                message: 'test',
            }))).toEqual(new ApiError(400, 'test', {}));
        });

        it('works ApiError JSON', () => {
            expect(api.getApiErrorFromResponseText(JSON.stringify({
                status: 400,
                error: {'field1': ['testError']},
                message: 'testMessage',
            }))).toEqual(new ApiError(400, 'testMessage', {'field1': ['testError']}));
        });
    });
});
