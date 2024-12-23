# php-typescript-api

Build a typed Web API using PHP and TypeScript

**Disclaimer: This is not an officially supported Google product**

This project:
- helps with data validation on the server side by providing [fields](https://github.com/allestuetsmerweh/php-typescript-api/tree/main/server/lib/Fields/FieldTypes) or [phpstan](https://phpstan.org/writing-php-code/phpdoc-types) to define allowed request/response types.
- generates a typed API client in TypeScript.
- consists of a 
  [server (PHP)](https://github.com/allestuetsmerweh/php-typescript-api/tree/main/server) library,
  a
  [client (TypeScript)](https://github.com/allestuetsmerweh/php-typescript-api/tree/main/client)
  library, and an 
  [example](https://github.com/allestuetsmerweh/php-typescript-api/tree/main/example)
  of its usage.

## Usage

### Server side (PHP)

- Install php-typescript-api using [composer](https://getcomposer.org/):

  `composer require allestuetsmerweh/php-typescript-api`

- Implement some endpoints for your API
  ([examples](https://github.com/allestuetsmerweh/php-typescript-api/tree/main/example/api/endpoints)).

- Define which endpoints your API contains
  ([example](https://github.com/allestuetsmerweh/php-typescript-api/blob/main/example/api/example_api.php)).

- Define how the TypeScript interface for your API should be generated
  ([example](https://github.com/allestuetsmerweh/php-typescript-api/blob/main/example/api/generate.php)).
    - Run that script in order to generate the TypeScript interface file:
      `php path/to/your/generate.php` ([example result](https://github.com/allestuetsmerweh/php-typescript-api/blob/main/example/web/ExampleApiTypes.ts))

    - You might want to run this script automatically when starting your local dev server, and check in your CI pipeline whether the committed TypeScript interface file is up-to-date.

- Have a publicly reachable PHP script that serves the API
  ([example](https://github.com/allestuetsmerweh/php-typescript-api/blob/main/example/web/example_api_server.php)).

### Client side (TypeScript)

- Install php-typescript-api using [npm](https://docs.npmjs.com/about-npm):

  `npm install --save php-typescript-api`

- Configure your API client
  ([example](https://github.com/allestuetsmerweh/php-typescript-api/blob/main/example/web/ExampleApi.ts)).

- Use your API client to make requests
  ([example](https://github.com/allestuetsmerweh/php-typescript-api/blob/main/example/web/index.ts)).

## Contribute

Build:
- Server: (no build necessary)
- Client: `npm run build`

Run tests:

- Server: `composer test`
- Client: `npm test`

Lint:

- Server: `composer fix`
- Client: `npm run lint`