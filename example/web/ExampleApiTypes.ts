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

export type SptLocation = {'id': string, 'name': string, 'coordinate': SptCoordinate};

export type SptConnection = {'sections': Array<SptSection>};

export type SptCoordinate = {'type': string, 'x': string, 'y': string};

export type SptSection = {'departure': SptStop, 'arrival': SptStop, 'passList': Array<SptStop>};

export type SptStop = {'stationId': string, 'arrival': (string | null), 'departure': (string | null), 'delay': (number | null), 'platform': (string | null)};

// eslint-disable-next-line no-shadow
export type ExampleApiEndpoint =
    'divideNumbers'|
    'squareRoot'|
    'searchSwissPublicTransportConnection'|
    'empty'|
    'divideNumbersTyped'|
    'squareRootTyped'|
    'searchSwissPublicTransportConnectionTyped'|
    'emptyTyped';

type ExampleApiEndpointMapping = {[key in ExampleApiEndpoint]: unknown};

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
    divideNumbersTyped: {'dividend': number, 'divisor': number},
    squareRootTyped: (number | number),
    searchSwissPublicTransportConnectionTyped: {'from': string, 'to': string, 'via': (Array<string> | null), 'date': string, 'time': string, 'isArrivalTime': (boolean | null)},
    emptyTyped: Record<string, never>,
}

export interface ExampleApiResponses extends ExampleApiEndpointMapping {
    divideNumbers: number,
    squareRoot: number,
    searchSwissPublicTransportConnection: {
            'stationById': {[key: string]: SPTransportLocation},
            'connections': Array<SPTransportConnection>,
        },
    empty: Record<string, never>,
    divideNumbersTyped: number,
    squareRootTyped: number,
    searchSwissPublicTransportConnectionTyped: {'stationById': {[key: string]: SptLocation}, 'connections': Array<SptConnection>},
    emptyTyped: Record<string, never>,
}

