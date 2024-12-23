<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\BackendTests\Common;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ExampleBackendTestCase extends TestCase {
    public const BACKEND_URL = 'http://127.0.0.1:30270/example_api_server.php';

    private static bool|string $initial_dir;
    private static mixed $process;

    public static function setUpBeforeClass(): void {
        self::$initial_dir = getcwd();
        chdir(__DIR__.'/../../../../example/');
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin is a pipe that the child will read from
            1 => ['pipe', 'w'],  // stdout is a pipe that the child will write to
            2 => ['pipe', 'w'], // stderr is a file to write to
        ];
        self::$process = proc_open('./run.sh', $descriptorspec, $pipes);
        sleep(3);
    }

    protected function setUp(): void {
        date_default_timezone_set('UTC');
    }

    /** @return array{output: bool|string, result: ?string, error: ?string, http_code: int} */
    public function callBackend(string $endpoint, mixed $input): array {
        $backend_url = self::BACKEND_URL;
        $url = "{$backend_url}/{$endpoint}";
        $json = json_encode($input);
        assert(!is_bool($json));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        $result = is_string($output) ? json_decode($output, true) : null;
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [
            'output' => $output,
            'result' => $result,
            'error' => $error,
            'http_code' => $http_code,
        ];
    }

    public static function tearDownAfterClass(): void {
        proc_terminate(self::$process);
        if (is_string(self::$initial_dir)) {
            chdir(self::$initial_dir);
        }
    }
}
