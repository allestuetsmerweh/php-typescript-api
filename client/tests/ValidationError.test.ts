/* eslint-env jasmine */

import {ValidationError} from '../lib/ValidationError';

describe('ValidationError', () => {
    it('works', () => {
        const validationErrors = {
            'field1': ['Is not a string', 'Cannot be null'],
            'field2': ['Does not match regex'],
        };
        const validationError = new ValidationError(
            'Validation failed',
            validationErrors,
        );
        expect(validationError.message).toEqual('Validation failed');
        expect(validationError.getValidationErrors()).toEqual(validationErrors);
    });

    it('works for empty message', () => {
        const validationError = new ValidationError('', {});
        expect(validationError.message).toEqual('');
        expect(validationError.getValidationErrors()).toEqual({});
    });
});
