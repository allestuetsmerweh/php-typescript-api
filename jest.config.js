/* eslint-env node */
/* global module */

const percentCoverage = (percent) => ({
    branches: percent,
    functions: percent,
    lines: percent,
    statements: percent,
});

const jestConfig = {
    transform: {
        '^.+\\.ts$': 'ts-jest',
    },
    testEnvironment: 'jsdom',
    testRegex: 'client/tests/.*\\.test\\.ts',
    testPathIgnorePatterns: ['node_modules/'],
    collectCoverage: true,
    maxConcurrency: 1,
    coverageThreshold: {
        './client/src/': percentCoverage(100),
    },
};
module.exports = jestConfig;
