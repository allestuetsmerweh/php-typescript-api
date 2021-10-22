/** ### This file is auto-generated, modifying is futile! ### */


// eslint-disable-next-line no-shadow
export enum ExampleApiEndpoint {
    divideNumbers = 'divideNumbers',
}

type ExampleApiEndpointMapping = {[key in ExampleApiEndpoint]: {[fieldId: string]: any}};

export interface ExampleApiRequests extends ExampleApiEndpointMapping {
    divideNumbers: {
            'dividend': number,
            'divisor': number,
        },
}

export interface ExampleApiResponses extends ExampleApiEndpointMapping {
    divideNumbers: number,
}

