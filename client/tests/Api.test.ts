/* eslint-env jasmine */

import fetch from 'unfetch';
import {Api} from '../src/Api';
import {ValidationError} from '../src/ValidationError';

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
                error: {
                    type: 'ValidationError',
                    validationErrors: ['Not gonna do that :/'],
                },
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
                    throw new Error(`Error was not an error: ${err}`);
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
                    throw new Error(`Error was not an error: ${err}`);
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
                    throw new Error(`Error was not an error: ${err}`);
                }
                expect(err.message).toBe('test-error');
            }
        });
    });

    describe('getValidationErrorFromResponseText', () => {
        let api: FakeApi;

        beforeEach(() => {
            api = new FakeApi();
        });

        it('works when there is no reponse text', () => {
            expect(api.getValidationErrorFromResponseText(undefined)).toEqual(undefined);
            expect(api.getValidationErrorFromResponseText('')).toEqual(undefined);
        });

        it('works for invalid JSON', () => {
            expect(api.getValidationErrorFromResponseText('invalid json')).toEqual(undefined);
        });

        it('works for non-ValidationError JSON', () => {
            expect(api.getValidationErrorFromResponseText(JSON.stringify(null))).toEqual(undefined);
            expect(api.getValidationErrorFromResponseText(JSON.stringify({}))).toEqual(undefined);
            expect(api.getValidationErrorFromResponseText(JSON.stringify({
                error: {},
            }))).toEqual(undefined);
            expect(api.getValidationErrorFromResponseText(JSON.stringify({
                error: {type: 'invalid'},
            }))).toEqual(undefined);
        });

        it('works without message', () => {
            expect(() => api.getValidationErrorFromResponseText(JSON.stringify({
                error: {
                    type: 'ValidationError',
                    validationErrors: ['test'],
                },
            }))).toThrow();
        });

        it('works without validation errors', () => {
            expect(() => api.getValidationErrorFromResponseText(JSON.stringify({
                error: {type: 'ValidationError'},
                message: 'test',
            }))).toThrow();
        });

        it('works ValidationError JSON', () => {
            expect(api.getValidationErrorFromResponseText(JSON.stringify({
                error: {
                    type: 'ValidationError',
                    validationErrors: {'field1': ['testError']},
                },
                message: 'testMessage',
            }))).toEqual(new ValidationError('testMessage', {'field1': ['testError']}));
        });
    });

    describe('mergeValidationErrors', () => {
        let api: FakeApi;

        beforeEach(() => {
            api = new FakeApi();
        });

        const validationError1 = new ValidationError(
            'testMessage1', {'field1': ['testError1']},
        );
        const validationError2 = new ValidationError(
            'testMessage2', {'field2': ['testError2']},
        );
        const validationError3 = new ValidationError(
            '', {'field3': ['testError3']},
        );
        const nullValidationError = new ValidationError('', {});

        function expectValidationErrorsEqual(
            expectError: ValidationError,
            toEqualError: ValidationError,
        ) {
            expect(expectError.message).toEqual(toEqualError.message);
            expect(expectError.getErrorsByField()).toEqual(toEqualError.getErrorsByField());
        }

        it('merges empty list of errors', () => {
            expect(api.mergeValidationErrors([])).toEqual(nullValidationError);
        });

        it('returns the validation error when merging just one', () => {
            const mergedError1 = api.mergeValidationErrors([validationError1]);
            const mergedError2 = api.mergeValidationErrors([validationError2]);
            const mergedError3 = api.mergeValidationErrors([validationError3]);
            expectValidationErrorsEqual(mergedError1, validationError1);
            expectValidationErrorsEqual(mergedError2, validationError2);
            expectValidationErrorsEqual(mergedError3, validationError3);
        });

        it('merges two validation errors', () => {
            const mergedError = api.mergeValidationErrors([
                validationError1,
                validationError2,
            ]);
            const expectedError = new ValidationError(
                'testMessage1\ntestMessage2',
                {
                    'field1': ['testError1'],
                    'field2': ['testError2'],
                },
            );
            expectValidationErrorsEqual(mergedError, expectedError);
        });

        it('merges three validation errors', () => {
            const mergedError = api.mergeValidationErrors([
                validationError1,
                validationError2,
                validationError3,
            ]);
            const expectedError = new ValidationError(
                'testMessage1\ntestMessage2',
                {
                    'field1': ['testError1'],
                    'field2': ['testError2'],
                    'field3': ['testError3'],
                },
            );
            expectValidationErrorsEqual(mergedError, expectedError);
        });

        it('concatenates errors on the same field', () => {
            const mergedError = api.mergeValidationErrors([
                validationError1,
                validationError1,
            ]);
            const expectedError = new ValidationError(
                'testMessage1\ntestMessage1',
                {
                    // TODO: This should be:
                    // 'field1': ['testError1', 'testError1'],
                    'field1': ['testError1'],
                },
            );
            expectValidationErrorsEqual(mergedError, expectedError);
        });
    });
});
