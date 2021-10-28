import {ExampleApiEndpoint, ExampleApiRequests, ExampleApiResponses} from './ExampleApiTypes';
import {Api} from 'php-typescript-api';

export class ExampleApi extends Api<ExampleApiEndpoint, ExampleApiRequests, ExampleApiResponses> {
    public baseUrl = 'http://127.0.0.1:30270/example_api_server.php';
}
