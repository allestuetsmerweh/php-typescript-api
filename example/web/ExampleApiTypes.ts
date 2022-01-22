/** ### This file is auto-generated, modifying is futile! ### */

export type SPTransportLocation = {
    'id': string,
    'name': string,
    'coordinate': SPTransportCoordinates,
};

export type SPTransportCoordinates = {
    'type': string,
    'x': number,
    'y': number,
};

export type SPTransportConnection = {
    'sections': Array<SPTransportSection>,
};

export type SPTransportSection = {
    'departure': SPTransportStop,
    'arrival': SPTransportStop,
    'passList': Array<SPTransportStop>,
};

export type SPTransportStop = {
    'stationId': string,
    'arrival': string|null,
    'departure': string|null,
    'delay': number|null,
    'platform': string|null,
};

// eslint-disable-next-line no-shadow
export type ExampleApiEndpoint =
    'divideNumbers'|
    'squareRoot'|
    'searchSwissPublicTransportConnection'|
    'empty';

type ExampleApiEndpointMapping = {[key in ExampleApiEndpoint]: any};

export interface ExampleApiRequests extends ExampleApiEndpointMapping {
    divideNumbers: {
            'dividend': number,
            'divisor': number,
        },
    squareRoot: number,
    searchSwissPublicTransportConnection: {
            'from': string,
            'to': string,
            'via': Array<string>|null,
            'date': string,
            'time': string,
            'isArrivalTime': boolean|null,
        },
    empty: Record<string, never>,
}

export interface ExampleApiResponses extends ExampleApiEndpointMapping {
    divideNumbers: number,
    squareRoot: number,
    searchSwissPublicTransportConnection: {
            'stationById': {[key: string]: SPTransportLocation},
            'connections': Array<SPTransportConnection>,
        },
    empty: Record<string, never>,
}

