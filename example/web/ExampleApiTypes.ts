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

export type DivideTypedEndpoint_T = DivideNumbersTypedEndpoint_DefaultNumberType;

export type DivideNumbersTypedEndpoint_DefaultNumberType = DivideTypedEndpoint_DefaultNumberType;

export type DivideTypedEndpoint_DefaultNumberType = number;

export type PhpTypeScriptApi_PhpStan_IsoDate = string;

export type _PhpTypeScriptApi_PhpStan_IsoTime = string;

export type PhpTypeScriptApi_PhpStan_IsoDateTime = string;

export type _PhpTypeScriptApi_PhpStan_IsoDate = string;

export type SwissPublicTransportConnectionsTypedEndpoint_SptLocation = {'id': string, 'name': string, 'coordinate': SwissPublicTransportConnectionsTypedEndpoint_SptCoordinate};

export type SwissPublicTransportConnectionsTypedEndpoint_SptConnection = {'sections': Array<SwissPublicTransportConnectionsTypedEndpoint_SptSection>};

export type SwissPublicTransportConnectionsTypedEndpoint_SptCoordinate = {'type': string, 'x': string, 'y': string};

export type SwissPublicTransportConnectionsTypedEndpoint_SptSection = {'departure': SwissPublicTransportConnectionsTypedEndpoint_SptStop, 'arrival': SwissPublicTransportConnectionsTypedEndpoint_SptStop, 'passList': Array<SwissPublicTransportConnectionsTypedEndpoint_SptStop>};

export type SwissPublicTransportConnectionsTypedEndpoint_SptStop = {'stationId': string, 'arrival': (string | null), 'departure': (string | null), 'delay': (number | null), 'platform': (string | null)};

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
    divideNumbersTyped: {'dividend': DivideTypedEndpoint_T, 'divisor': DivideTypedEndpoint_T},
    squareRootTyped: (number | number),
    combineDateTimeTyped: {'date': PhpTypeScriptApi_PhpStan_IsoDate, 'time': _PhpTypeScriptApi_PhpStan_IsoTime},
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
    divideNumbersTyped: DivideTypedEndpoint_T,
    squareRootTyped: number,
    combineDateTimeTyped: {'dateTime': PhpTypeScriptApi_PhpStan_IsoDateTime},
    searchSwissPublicTransportConnectionTyped: {'stationById': {[key: string]: SwissPublicTransportConnectionsTypedEndpoint_SptLocation}, 'connections': Array<SwissPublicTransportConnectionsTypedEndpoint_SptConnection>},
    emptyTyped: Record<string, never>,
}

