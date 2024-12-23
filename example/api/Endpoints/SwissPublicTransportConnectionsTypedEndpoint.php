<?php

use PhpTypeScriptApi\PhpStan\NamedType;
use PhpTypeScriptApi\TypedEndpoint;

/** @extends NamedType<array{type: string, x: string, y: string}> */
class SptCoordinate extends NamedType {
}

/** @extends NamedType<array{id: string, name: string, coordinate: SptCoordinate}> */
class SptLocation extends NamedType {
}

/** @extends NamedType<array{stationId: string, arrival: ?string, departure: ?string, delay: ?int, platform: ?string}> */
class SptStop extends NamedType {
}

/** @extends NamedType<array{departure: SptStop, arrival: SptStop, passList: array<SptStop>}> */
class SptSection extends NamedType {
}

/** @extends NamedType<array{sections: array<SptSection>}> */
class SptConnection extends NamedType {
}

/**
 * Search for a swiss public transport connection.
 *
 * for further information on the backend used, see
 * https://transport.opendata.ch/docs.html#connections
 *
 * @extends TypedEndpoint<
 *   array{'from': string, 'to': string, 'via': ?array<string>, 'date': string, 'time': string, 'isArrivalTime': ?bool},
 *   array{stationById: array<string, SptLocation>, connections: array<SptConnection>},
 * >
 */
class SwissPublicTransportConnectionsTypedEndpoint extends TypedEndpoint {
    public static function getIdent(): string {
        return 'SwissPublicTransportConnectionsTypedEndpoint';
    }

    protected function handle(mixed $input): mixed {
        $base_url = 'https://transport.opendata.ch/v1/connections';
        $get_params = http_build_query([
            'from' => $input['from'],
            'to' => $input['to'],
            'via' => $input['via'],
            'date' => $input['date'],
            'time' => $input['time'],
            'isArrivalTime' => $input['isArrivalTime'],
        ]);
        $backend_response = file_get_contents("{$base_url}?{$get_params}");
        if (!$backend_response) {
            return ['stationById' => [], 'connections' => []];
        }
        $backend_result = json_decode($backend_response, true);
        return [
            'stationById' => $this->getStationByIds($backend_result),
            'connections' => $this->getConnections($backend_result),
        ];
    }

    /**
     * @param array<string, mixed> $backend_result
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

    /** @param array<string, mixed> $backend_station */
    protected function convertStation(array $backend_station): SptLocation {
        return new SptLocation([
            'id' => $backend_station['id'],
            'name' => $backend_station['name'],
            'coordinate' => $this->convertCoordinate($backend_station['coordinate']),
        ]);
    }

    /** @param array<string, mixed> $args */
    protected function convertCoordinate(array $args): SptCoordinate {
        return new SptCoordinate([
            'type' => $args['type'],
            'x' => $args['x'],
            'y' => $args['y'],
        ]);
    }

    /**
     * @param array<string, mixed> $backend_result
     *
     * @return array<SptConnection>
     */
    protected function getConnections(array $backend_result): array {
        $new_connections = [];
        foreach ($backend_result['connections'] as $connection) {
            $new_sections = [];
            foreach ($connection['sections'] as $section) {
                $new_section = new SptSection([
                    'departure' => $this->convertStop($section['departure']),
                    'arrival' => $this->convertStop($section['arrival']),
                    'passList' => array_map(
                        fn ($stop) => $this->convertStop($stop),
                        $section['journey']['passList'],
                    ),
                ]);
                $new_sections[] = $new_section;
            }
            $new_connections[] = new SptConnection(['sections' => $new_sections]);
        }
        return $new_connections;
    }

    /** @param array<string, mixed> $backend_stop */
    protected function convertStop(array $backend_stop): SptStop {
        return new SptStop([
            'stationId' => $backend_stop['station']['id'],
            'arrival' => $this->getTime($backend_stop['arrival'] ?? null),
            'departure' => $this->getTime($backend_stop['departure'] ?? null),
            'delay' => $backend_stop['delay'],
            'platform' => $backend_stop['platform'],
        ]);
    }

    protected function getTime(?string $backend_value): ?string {
        if (!$backend_value) {
            return null;
        }
        return substr($backend_value, 11, 8);
    }
}
