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

export function submitSPTransportConnectionForm(form: HTMLFormElement): boolean {
    const fromField = form.elements.namedItem('from');
    const toField = form.elements.namedItem('to');
    const viaField = form.elements.namedItem('via');
    const dateField = form.elements.namedItem('date');
    const timeField = form.elements.namedItem('time');
    const isArrivalTimeField = form.elements.namedItem('isArrivalTime');
    const resultField = form.elements.namedItem('result');
    exampleApi.call('searchSwissPublicTransportConnection', {
        from: fromField && 'value' in fromField ? fromField.value : '',
        to: toField && 'value' in toField ? toField.value : '',
        via: viaField && 'value' in viaField && viaField.value ? [viaField.value] : [],
        date: dateField && 'value' in dateField ? dateField.value : '',
        time: timeField && 'value' in timeField ? timeField.value : '',
        isArrivalTime: isArrivalTimeField && 'value' in isArrivalTimeField ? isArrivalTimeField.value === '1' : false,
    }).then((result) => {
        if (resultField && 'value' in resultField) {
            resultField.value = `The result is ${result}`;
        }
    });
    return false;
}

export function submitDivideFormTyped(form: HTMLFormElement): boolean {
    const dividendField = form.elements.namedItem('dividend');
    const divisorField = form.elements.namedItem('divisor');
    const resultField = form.elements.namedItem('result');
    exampleApi.call('divideNumbersTyped', {
        dividend: Number(dividendField && 'value' in dividendField ? dividendField.value : null),
        divisor: Number(divisorField && 'value' in divisorField ? divisorField.value : null),
    }).then((result) => {
        if (resultField && 'value' in resultField) {
            resultField.value = `The result is ${result}`;
        }
    });
    return false;
}

export function submitSqrtFormTyped(form: HTMLFormElement): boolean {
    const inputField = form.elements.namedItem('input');
    const resultField = form.elements.namedItem('result');
    exampleApi.call('squareRootTyped', Number(inputField && 'value' in inputField ? inputField.value : null)).then((result) => {
        if (resultField && 'value' in resultField) {
            resultField.value = `The result is ${result}`;
        }
    });
    return false;
}

export function submitCombineDateTimeFormTyped(form: HTMLFormElement): boolean {
    const dateField = form.elements.namedItem('date');
    const timeField = form.elements.namedItem('time');
    const resultField = form.elements.namedItem('result');
    exampleApi.call('combineDateTimeTyped', {
        date: dateField && 'value' in dateField ? dateField.value : '',
        time: timeField && 'value' in timeField ? timeField.value : '',
    }).then((result) => {
        if (resultField && 'value' in resultField) {
            resultField.value = `The result is ${result.dateTime}`;
        }
    });
    return false;
}

export function submitSPTransportConnectionFormTyped(form: HTMLFormElement): boolean {
    const fromField = form.elements.namedItem('from');
    const toField = form.elements.namedItem('to');
    const viaField = form.elements.namedItem('via');
    const dateField = form.elements.namedItem('date');
    const timeField = form.elements.namedItem('time');
    const isArrivalTimeField = form.elements.namedItem('isArrivalTime');
    const resultField = form.elements.namedItem('result');
    exampleApi.call('searchSwissPublicTransportConnectionTyped', {
        from: fromField && 'value' in fromField ? fromField.value : '',
        to: toField && 'value' in toField ? toField.value : '',
        via: viaField && 'value' in viaField && viaField.value ? [viaField.value] : [],
        date: dateField && 'value' in dateField ? dateField.value : '',
        time: timeField && 'value' in timeField ? timeField.value : '',
        isArrivalTime: isArrivalTimeField && 'value' in isArrivalTimeField ? isArrivalTimeField.value === '1' : false,
    }).then((result) => {
        if (resultField && 'value' in resultField) {
            resultField.value = `The result is ${result}`;
        }
    });
    return false;
}

