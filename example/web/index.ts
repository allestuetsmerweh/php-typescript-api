import {ExampleApiEndpoint, ExampleApiRequests, ExampleApiResponses} from './ExampleApiTypes';
import {Api} from '../../client/lib/Api';

class ExampleApi extends Api<ExampleApiEndpoint, ExampleApiRequests, ExampleApiResponses> {
    public baseUrl = 'http://127.0.0.1:30270/example_api_server.php';
}

const exampleApi = new ExampleApi();

export function submitDivideForm(form: HTMLFormElement): boolean {
    const dividendField = form.elements.namedItem('dividend');
    const divisorField = form.elements.namedItem('divisor');
    const resultField = form.elements.namedItem('result');
    exampleApi.call('divideNumbers', {
        dividend: Number(dividendField && 'value' in dividendField ? dividendField.value : null),
        divisor: Number(divisorField && 'value' in divisorField ? divisorField.value : null),
    }).then((result) => {
        if (resultField && 'value' in resultField) {
            resultField.value = `The result is ${result}`;
        }
    });
    return false;
}
