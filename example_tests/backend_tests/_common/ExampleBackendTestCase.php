<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ExampleBackendTestCase extends TestCase {
    public const BACKEND_URL = 'http://127.0.0.1:30270/example_api_server.php';

    private static $initial_dir;
    private static $process;

    public static function setUpBeforeClass(): void {
        self::$initial_dir = getcwd();
        chdir(__DIR__.'/../../../example/');
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin is a pipe that the child will read from
            1 => ['pipe', 'w'],  // stdout is a pipe that the child will write to
            2 => ['pipe', 'w'], // stderr is a file to write to
        ];
        self::$process = proc_open('./run.sh', $descriptorspec, $pipes);
    }

    protected function setUp(): void {
        date_default_timezone_set('UTC');
    }

    public function callBackend($endpoint, $input) {
        $backend_url = self::BACKEND_URL;
        $url = "{$backend_url}/{$endpoint}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $result = $output ? json_decode($output, true) : null;
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
        chdir(self::$initial_dir);
    }
}
