/* eslint-env jasmine */

import {ValidationError, ErrorsByField} from '../src/ValidationError';

describe('ValidationError', () => {
    it('works', () => {
        const validationErrors: ErrorsByField = {
            'field1': ['Is not a string', 'Cannot be null'],
            'field2': ['Does not match regex'],
        };
        const validationError = new ValidationError(
            'Validation failed',
            validationErrors,
        );
        expect(validationError.message).toEqual('Validation failed');
        expect(validationError.getErrorsByField()).toEqual(validationErrors);
    });

    it('works for empty message', () => {
        const validationError = new ValidationError('', {});
        expect(validationError.message).toEqual('');
        expect(validationError.getErrorsByField()).toEqual({});
    });

    const validationErrors: ErrorsByField = {
        '.': ['Existential problem.', 'Another existential problem'],
        'a': [
            'Problem in key a',
            'Another problem in key a',
            {
                '.': ['Problem from field a'],
                'aa': [
                    'Problem in key aa',
                    {
                        '.': ['Problem from field aa'],
                        'aaa': ['Problem in key aaa'],
                    },
                ],
                'ab': ['Problem in key ab'],
            },
            {
                '.': ['Another problem from field a'],
                'aa': [
                    'Another problem in key aa',
                    {'aaa': ['Another problem in key aaa']},
                ],
                'ac': ['Problem in key ac'],
            },
        ],
    };
    const validationError = new ValidationError(
        'Validation failed',
        validationErrors,
    );

    describe('getErrorsForField', () => {
        it('works for a', () => {
            expect(validationError.getErrorsForField('a'))
                .toEqual([
                    'Problem in key a',
                    'Another problem in key a',
                    'Problem from field a',
                    'Another problem from field a',
                    'Key \'aa\': Problem in key aa',
                    'Key \'aa\': Problem from field aa',
                    'Key \'aa\': Key \'aaa\': Problem in key aaa',
                    'Key \'aa\': Another problem in key aa',
                    'Key \'aa\': Key \'aaa\': Another problem in key aaa',
                    'Key \'ab\': Problem in key ab',
                    'Key \'ac\': Problem in key ac',
                ]);
        });
    });

    describe('getErrorsForErrorsByField', () => {
        it('works for a', () => {
            expect(validationError.getErrorsForFieldPath(['a']))
                .toEqual([
                    'Problem in key a',
                    'Another problem in key a',
                    'Problem from field a',
                    'Another problem from field a',
                    'Key \'aa\': Problem in key aa',
                    'Key \'aa\': Problem from field aa',
                    'Key \'aa\': Key \'aaa\': Problem in key aaa',
                    'Key \'aa\': Another problem in key aa',
                    'Key \'aa\': Key \'aaa\': Another problem in key aaa',
                    'Key \'ab\': Problem in key ab',
                    'Key \'ac\': Problem in key ac',
                ]);
        });

        it('works for a > aa', () => {
            expect(validationError.getErrorsForFieldPath(['a', 'aa']))
                .toEqual([
                    'Problem in key aa',
                    'Problem from field aa',
                    'Another problem in key aa',
                    'Key \'aaa\': Problem in key aaa',
                    'Key \'aaa\': Another problem in key aaa',
                ]);
        });

        it('works for a > aa > aaa', () => {
            expect(validationError.getErrorsForFieldPath(['a', 'aa', 'aaa']))
                .toEqual([
                    'Problem in key aaa',
                    'Another problem in key aaa',
                ]);
        });

        it('works for a > ab', () => {
            expect(validationError.getErrorsForFieldPath(['a', 'ab']))
                .toEqual(['Problem in key ab']);
        });

        it('works for a > ac', () => {
            expect(validationError.getErrorsForFieldPath(['a', 'ac']))
                .toEqual(['Problem in key ac']);
        });
    });

    describe('getErrorsByFieldForFieldPath', () => {
        it('works for . (root)', () => {
            expect(validationError.getErrorsByFieldForFieldPath([]))
                .toEqual(validationErrors);
        });

        it('works for a', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['a']))
                .toEqual({
                    '.': [
                        'Problem in key a',
                        'Another problem in key a',
                        'Problem from field a',
                        'Another problem from field a',
                    ],
                    'aa': [
                        'Problem in key aa',
                        {
                            '.': ['Problem from field aa'],
                            'aaa': ['Problem in key aaa'],
                        },
                        'Another problem in key aa',
                        {'aaa': ['Another problem in key aaa']},
                    ],
                    'ab': ['Problem in key ab'],
                    'ac': ['Problem in key ac'],
                });
        });

        it('works for a > aa', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['a', 'aa']))
                .toEqual({
                    '.': [
                        'Problem in key aa',
                        'Problem from field aa',
                        'Another problem in key aa',
                    ],
                    'aaa': [
                        'Problem in key aaa',
                        'Another problem in key aaa',
                    ],
                });
        });

        it('works for a > aa > aaa', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['a', 'aa', 'aaa']))
                .toEqual({
                    '.': [
                        'Problem in key aaa',
                        'Another problem in key aaa',
                    ],
                });
        });

        it('works for a > ab', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['a', 'ab']))
                .toEqual({'.': ['Problem in key ab']});
        });

        it('works for a > ac', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['a', 'ac']))
                .toEqual({'.': ['Problem in key ac']});
        });

        it('works for .', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['.']))
                .toEqual({'.': [
                    'Existential problem.',
                    'Another existential problem',
                ]});
        });

        it('works for a > .', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['a', '.']))
                .toEqual({'.': [
                    'Problem in key a',
                    'Another problem in key a',
                    'Problem from field a',
                    'Another problem from field a',
                ]});
        });

        it('works for a > aa > .', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['a', 'aa', '.']))
                .toEqual({'.': [
                    'Problem in key aa',
                    'Problem from field aa',
                    'Another problem in key aa',
                ]});
        });

        it('works for a > aa > aaa > .', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['a', 'aa', 'aaa', '.']))
                .toEqual({'.': [
                    'Problem in key aaa',
                    'Another problem in key aaa',
                ]});
        });

        it('works for a > ab > .', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['a', 'ab', '.']))
                .toEqual({'.': ['Problem in key ab']});
        });

        it('works for a > ac > .', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['a', 'ac', '.']))
                .toEqual({'.': ['Problem in key ac']});
        });

        it('works for inexistent', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['inexistent']))
                .toEqual({});
        });

        it('works for inexistent > inexistent', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['inexistent', 'inexistent']))
                .toEqual({});
        });

        it('works for a > inexistent', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['a', 'inexistent']))
                .toEqual({});
        });

        it('works for a > . > inexistent', () => {
            expect(validationError.getErrorsByFieldForFieldPath(['a', '.', 'inexistent']))
                .toEqual({});
        });
    });

    describe('getErrorsForErrorsByField', () => {
        it('works', () => {
            expect(validationError.getErrorsForErrorsByField(validationErrors))
                .toEqual([
                    'Existential problem.',
                    'Another existential problem',
                    'Key \'a\': Problem in key a',
                    'Key \'a\': Another problem in key a',
                    'Key \'a\': Problem from field a',
                    'Key \'a\': Key \'aa\': Problem in key aa',
                    'Key \'a\': Key \'aa\': Problem from field aa',
                    'Key \'a\': Key \'aa\': Key \'aaa\': Problem in key aaa',
                    'Key \'a\': Key \'ab\': Problem in key ab',
                    'Key \'a\': Another problem from field a',
                    'Key \'a\': Key \'aa\': Another problem in key aa',
                    'Key \'a\': Key \'aa\': Key \'aaa\': Another problem in key aaa',
                    'Key \'a\': Key \'ac\': Problem in key ac',
                ]);
        });
    });
});
