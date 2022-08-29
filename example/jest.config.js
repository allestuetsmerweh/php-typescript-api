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
    testEnvironmentOptions: {
        url: 'http://127.0.0.1:30270',
    },
    testRegex: 'tests/.*\\.test\\.ts',
    testPathIgnorePatterns: ['node_modules/'],
    collectCoverage: true,
    maxConcurrency: 1,
    coverageThreshold: {
        './web/': percentCoverage(100),
    },
};
module.exports = jestConfig;
