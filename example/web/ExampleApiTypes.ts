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

export type DefaultNumberType = number;

export type IsoDate = string;

export type _PhpTypeScriptApi_PhpStan_IsoTime = string;

export type IsoDateTime = string;

export type _PhpTypeScriptApi_PhpStan_IsoDate = string;

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
    'combineDateTimeTyped'|
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
    divideNumbersTyped: {'dividend': DefaultNumberType, 'divisor': DefaultNumberType},
    squareRootTyped: (number | number),
    combineDateTimeTyped: {'date': IsoDate, 'time': _PhpTypeScriptApi_PhpStan_IsoTime},
    searchSwissPublicTransportConnectionTyped: {'from': string, 'to': string, 'via': (Array<string> | null), 'date': _PhpTypeScriptApi_PhpStan_IsoDate, 'time': string, 'isArrivalTime': (boolean | null)},
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
    divideNumbersTyped: DefaultNumberType,
    squareRootTyped: number,
    combineDateTimeTyped: {'dateTime': IsoDateTime},
    searchSwissPublicTransportConnectionTyped: {'stationById': {[key: string]: SptLocation}, 'connections': Array<SptConnection>},
    emptyTyped: Record<string, never>,
}

