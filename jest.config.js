/* eslint-env node */

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
    testRegex: '.*/.*\\.test\\.ts',
    testPathIgnorePatterns: ['node_modules/'],
    collectCoverage: true,
    maxConcurrency: 1,
    coverageThreshold: {
        './client/lib/': percentCoverage(100),
    },
};
module.exports = jestConfig;
