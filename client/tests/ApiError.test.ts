import {ApiError} from '../src/ApiError';

describe('ApiError', () => {
    it('works', () => {
        const error = new ApiError(401, 'Unauthorized');
        expect(error.status).toEqual(401);
        expect(error.message).toEqual('Unauthorized');
        expect(error.errorsByField).toEqual(undefined);
    });

    it('works with errors by field', () => {
        const error = new ApiError(401, 'Unauthorized', {'location.latitude': ['Must be a number']});
        expect(error.status).toEqual(401);
        expect(error.message).toEqual('Unauthorized');
        expect(error.errorsByField).toEqual({'location.latitude': ['Must be a number']});
    });

    it('works for empty message', () => {
        const error = new ApiError(500, '');
        expect(error.status).toEqual(500);
        expect(error.message).toEqual('');
        expect(error.errorsByField).toEqual(undefined);
    });
});
