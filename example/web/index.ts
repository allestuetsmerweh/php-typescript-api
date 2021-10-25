import {ExampleApi} from './ExampleApi';

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

export function submitSqrtForm(form: HTMLFormElement): boolean {
    const inputField = form.elements.namedItem('input');
    const resultField = form.elements.namedItem('result');
    exampleApi.call('squareRoot', Number(inputField && 'value' in inputField ? inputField.value : null)).then((result) => {
        if (resultField && 'value' in resultField) {
            resultField.value = `The result is ${result}`;
        }
    });
    return false;
}
