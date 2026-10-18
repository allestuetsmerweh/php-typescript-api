<?php

use PhpTypeScriptApi\TypedEndpoint;

/**
 * Search for a swiss public transport connection.
 *
 * for further information on the backend used, see
 * https://transport.opendata.ch/docs.html#connections
 *
 * @phpstan-type SptCoordinate array{type: string, x: string, y: string}
 * @phpstan-type SptLocation array{id: string, name: string, coordinate: SptCoordinate}
 * @phpstan-type SptStop array{stationId: string, arrival: ?string, departure: ?string, delay: ?int, platform: ?string}
 * @phpstan-type SptSection array{departure: SptStop, arrival: SptStop, passList: array<SptStop>}
 * @phpstan-type SptConnection array{sections: array<SptSection>}
 * @phpstan-type SptBackendCoordinate array{type: string, x: string, y: string}
 * @phpstan-type SptBackendLocation array{id: string, name: string, coordinate: SptBackendCoordinate}
 * @phpstan-type SptBackendStop array{station: ?SptBackendLocation, arrival: ?string, departure: ?string, delay: ?int, platform: ?string}
 * @phpstan-type SptBackendSection array{departure: SptBackendStop, arrival: SptBackendStop, journey: array{passList: array<SptBackendStop>}}
 * @phpstan-type SptBackendConnection array{sections: array<SptBackendSection>}
 * @phpstan-type SptBackendResult array{connections: array<SptBackendConnection>}
 *
 * @extends TypedEndpoint<
 *   array{'from': string, 'to': string, 'via': ?array<string>, 'date': \PhpTypeScriptApi\PhpStan\IsoDate, 'time': string, 'isArrivalTime': ?bool},
 *   array{stationById: array<string, SptLocation>, connections: array<SptConnection>},
 * >
 */
class SwissPublicTransportConnectionsTypedEndpoint extends TypedEndpoint {
    protected function handle(mixed $input): mixed {
        $base_url = 'https://transport.opendata.ch/v1/connections';
        $get_params = http_build_query([
            'from' => $input['from'],
            'to' => $input['to'],
            'via' => $input['via'],
            'date' => $input['date']->format('Y-m-d'),
            'time' => $input['time'],
            'isArrivalTime' => $input['isArrivalTime'],
        ]);
        $backend_response = file_get_contents("{$base_url}?{$get_params}");
        if (!$backend_response) {
            return ['stationById' => [], 'connections' => []];
        }
        $backend_result = json_decode($backend_response, true);
        return [
            // @phpstan-ignore argument.type
            'stationById' => $this->getStationByIds($backend_result),
            // @phpstan-ignore argument.type
            'connections' => $this->getConnections($backend_result),
        ];
    }

    /**
     * @param SptBackendResult $backend_result
     *
     * @return array<string, SptLocation>
     */
    protected function getStationByIds(array $backend_result): array {
        $station_by_id = [];
        foreach ($backend_result['connections'] as $connection) {
            foreach ($connection['sections'] as $section) {
                $departure = $section['departure']['station'] ?? null;
                if ($departure) {
                    $key = $departure['id'];
                    $station_by_id["{$key}"] = $this->convertStation($departure);
                }
                $arrival = $section['arrival']['station'] ?? null;
                if ($arrival) {
                    $key = $arrival['id'];
                    $station_by_id["{$key}"] = $this->convertStation($arrival);
                }
            }
        }
        return $station_by_id;
    }

    /**
     * @param SptBackendLocation $backend_station
     *
     * @return SptLocation
     */
    protected function convertStation(array $backend_station): array {
        return [
            'id' => $backend_station['id'],
            'name' => $backend_station['name'],
            'coordinate' => $this->convertCoordinate($backend_station['coordinate']),
        ];
    }

    /**
     * @param SptBackendCoordinate $args
     *
     * @return SptCoordinate
     */
    protected function convertCoordinate(array $args): array {
        return [
            'type' => $args['type'],
            'x' => $args['x'],
            'y' => $args['y'],
        ];
    }

    /**
     * @param SptBackendResult $backend_result
     *
     * @return array<SptConnection>
     */
    protected function getConnections(array $backend_result): array {
        $new_connections = [];
        foreach ($backend_result['connections'] as $connection) {
            $new_sections = [];
            foreach ($connection['sections'] as $section) {
                $new_section = [
                    'departure' => $this->convertStop($section['departure']),
                    'arrival' => $this->convertStop($section['arrival']),
                    'passList' => array_map(
                        fn ($stop) => $this->convertStop($stop),
                        $section['journey']['passList'],
                    ),
                ];
                $new_sections[] = $new_section;
            }
            $new_connections[] = ['sections' => $new_sections];
        }
        return $new_connections;
    }

    /**
     * @param SptBackendStop $backend_stop
     *
     * @return SptStop
     */
    protected function convertStop(array $backend_stop): array {
        $station = $backend_stop['station'] ?? null;
        if ($station === null) {
            throw new Exception('Backend stop must contain a station.');
        }
        return [
            'stationId' => $station['id'],
            'arrival' => $this->getTime($backend_stop['arrival'] ?? null),
            'departure' => $this->getTime($backend_stop['departure'] ?? null),
            'delay' => $backend_stop['delay'],
            'platform' => $backend_stop['platform'],
        ];
    }

    protected function getTime(?string $backend_value): ?string {
        if (!$backend_value) {
            return null;
        }
        return substr($backend_value, 11, 8);
    }
}
