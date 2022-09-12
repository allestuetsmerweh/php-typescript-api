/* eslint-env jasmine */

import {spawn, ChildProcess} from 'child_process';
import path from 'path';
import http from 'http';
import {ExampleApi} from '../../web/ExampleApi';
import {ValidationError} from 'php-typescript-api';

describe('', () => {
    let server: ChildProcess;

    beforeAll(async () => {
        const examplePath = path.resolve(__dirname, '..', '..');
        const serverUrl = 'http://127.0.0.1:30270';

        server = spawn('./run.sh', [], {
            detached: true,
            shell: true,
            cwd: examplePath,
        });
        await waitFor(1000);
        if (await getHttpStatusCode(serverUrl) === 200) {
            return;
        }
        await waitFor(2000);
        if (await getHttpStatusCode(serverUrl) === 200) {
            return;
        }
        await waitFor(4000);
        if (await getHttpStatusCode(serverUrl) === 200) {
            return;
        }
        throw new Error('Timeout starting example server');
    });

    afterAll(() => {
        if (server.pid) {
            try {
                // On CI, only this works!
                process.kill(-server.pid);
            } catch (e: unknown) {
                server.kill();
            }
        } else {
            server.kill();
        }
    });

    describe('divideNumbers', () => {
        it('works end to end', async () => {
            const exampleApi = new ExampleApi();

            const result1 = await exampleApi.call('divideNumbers', {dividend: 6, divisor: 3});
            expect(result1).toEqual(2);

            const result2 = await exampleApi.call('divideNumbers', {dividend: 6, divisor: 12});
            expect(result2).toEqual(0.5);

            const result3 = await exampleApi.call('divideNumbers', {dividend: 4, divisor: 3});
            expect(result3).toEqual(1.3333333333333333);
        });

        it('works end to end in error case', async () => {
            const exampleApi = new ExampleApi();

            try {
                await exampleApi.call('divideNumbers', {dividend: 6, divisor: 0});
                throw new Error('error expected');
            } catch (err: unknown) {
                if (!(err instanceof ValidationError)) {
                    throw new Error('ValidationError expected');
                }
                expect(err.message).toEqual('Bad input');
                expect(err.getErrorsByField()).toEqual({
                    'divisor': ['Cannot divide by zero.'],
                });
            }
        });

        it('works end to end', async () => {
            const exampleApi = new ExampleApi();

            const result = await exampleApi.call('squareRoot', 16);

            expect(result).toEqual(4);
        });

        it('works end to end in error case', async () => {
            const exampleApi = new ExampleApi();

            try {
                await exampleApi.call('squareRoot', -1);
                throw new Error('error expected');
            } catch (err: unknown) {
                if (!(err instanceof ValidationError)) {
                    throw new Error('ValidationError expected');
                }
                expect(err.message).toEqual('Bad input');
                expect(err.getErrorsByField()).toEqual({
                    '.': ['Value must not be less than 0.'],
                });
            }
        });
    });

    describe('empty', () => {
        it('works end to end', async () => {
            const exampleApi = new ExampleApi();

            const result1 = await exampleApi.call('empty', {});
            expect(result1).toEqual([]);
        });
    });

    describe('squareRoot', () => {
        it('works end to end', async () => {
            const exampleApi = new ExampleApi();

            const result1 = await exampleApi.call('squareRoot', 9);
            expect(result1).toEqual(3);

            const result2 = await exampleApi.call('squareRoot', 2);
            expect(result2).toEqual(1.4142135623730951);
        });

        it('works end to end for zero case', async () => {
            const exampleApi = new ExampleApi();

            const result1 = await exampleApi.call('squareRoot', 0);
            expect(result1).toEqual(0);
        });

        it('works end to end in error case', async () => {
            const exampleApi = new ExampleApi();

            try {
                await exampleApi.call('squareRoot', -1);
                throw new Error('error expected');
            } catch (err: unknown) {
                if (!(err instanceof ValidationError)) {
                    throw new Error('ValidationError expected');
                }
                expect(err.message).toEqual('Bad input');
                expect(err.getErrorsByField()).toEqual({
                    '.': ['Value must not be less than 0.'],
                });
            }
        });
    });
});

function getHttpStatusCode(url: string) {
    return new Promise((resolve) => {
        const req = http.request(url, (res) => {
            resolve(res.statusCode);
        });
        req.on('error', () => {
            resolve(0);
        });
        req.end();
    });
}

function waitFor(milliseconds: number) {
    return new Promise((resolve) => {
        setTimeout(() => {
            resolve(milliseconds);
        }, milliseconds);
    });
}
