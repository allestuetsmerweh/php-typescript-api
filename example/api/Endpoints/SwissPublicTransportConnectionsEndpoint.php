<?php

use PhpTypeScriptApi\Endpoint;
use PhpTypeScriptApi\Fields\FieldTypes;

/**
 * Search for a swiss public transport connection.
 *
 * for further information on the backend used, see
 * https://transport.opendata.ch/docs.html#connections
 */
class SwissPublicTransportConnectionsEndpoint extends Endpoint {
    public function runtimeSetup(): void {
        // no runtime setup required.
    }

    public function getResponseField(): FieldTypes\Field {
        /** A geographic location. */
        $coordinates_field = new FieldTypes\ObjectField([
            'field_structure' => [
                'type' => new FieldTypes\StringField(),
                'x' => new FieldTypes\NumberField(),
                'y' => new FieldTypes\NumberField(),
            ],
            'export_as' => 'SPTransportCoordinates',
        ]);
        /** A public transport location ( = station). */
        $location_field = new FieldTypes\ObjectField([
            'field_structure' => [
                'id' => new FieldTypes\StringField(),
                'name' => new FieldTypes\StringField(),
                'coordinate' => $coordinates_field,
            ],
            'export_as' => 'SPTransportLocation',
        ]);
        /** A stop at a public transport station. */
        $stop_field = new FieldTypes\ObjectField([
            'field_structure' => [
                'stationId' => new FieldTypes\StringField(),
                'arrival' => new FieldTypes\TimeField(['allow_null' => true]),
                'departure' => new FieldTypes\TimeField(['allow_null' => true]),
                'delay' => new FieldTypes\NumberField(['allow_null' => true]),
                'platform' => new FieldTypes\StringField(['allow_null' => true]),
            ],
            'export_as' => 'SPTransportStop',
        ]);
        /**
         * A section (train, bus, tram, walk, etc.) of a public transport
         * connection.
         */
        $section_field = new FieldTypes\ObjectField([
            'field_structure' => [
                'departure' => $stop_field,
                'arrival' => $stop_field,
                'passList' => new FieldTypes\ArrayField([
                    'item_field' => $stop_field,
                ]),
            ],
            'export_as' => 'SPTransportSection',
        ]);
        /** A public transport connection, from source to destination. */
        $connection_field = new FieldTypes\ObjectField([
            'field_structure' => [
                'sections' => new FieldTypes\ArrayField([
                    'item_field' => $section_field,
                ]),
            ],
            'export_as' => 'SPTransportConnection',
        ]);
        return new FieldTypes\ObjectField([
            'field_structure' => [
                'stationById' => new FieldTypes\DictField([
                    'item_field' => $location_field,
                ]),
                'connections' => new FieldTypes\ArrayField([
                    'item_field' => $connection_field,
                ]),
            ],
        ]);
    }

    public function getRequestField(): FieldTypes\Field {
        return new FieldTypes\ObjectField([
            'field_structure' => [
                'from' => new FieldTypes\StringField([]),
                'to' => new FieldTypes\StringField([]),
                'via' => new FieldTypes\ArrayField([
                    'item_field' => new FieldTypes\StringField([]),
                    'allow_null' => true,
                ]),
                'date' => new FieldTypes\DateField([]),
                'time' => new FieldTypes\TimeField([]),
                'isArrivalTime' => new FieldTypes\BooleanField([
                    'allow_null' => true,
                ]),
            ],
        ]);
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
     * @return array<int|string, array{id: string, name: string, coordinate: array{type: string, x: int|float, y: int|float}}>
     */
    protected function getStationByIds(array $backend_result): array {
        $station_by_id = [];
        foreach ($backend_result['connections'] as $connection) {
            foreach ($connection['sections'] as $section) {
                $departure = $section['departure']['station'] ?? null;
                if ($departure) {
                    $station_by_id[$departure['id']] = $this->convertStation($departure);
                }
                $arrival = $section['arrival']['station'] ?? null;
                if ($arrival) {
                    $station_by_id[$arrival['id']] = $this->convertStation($arrival);
                }
            }
        }
        return $station_by_id;
    }

    /**
     * @param array<string, mixed> $backend_station
     *
     * @return array{id: string, name: string, coordinate: array{type: string, x: int|float, y: int|float}}
     */
    protected function convertStation(array $backend_station): array {
        return [
            'id' => $backend_station['id'],
            'name' => $backend_station['name'],
            'coordinate' => [
                'type' => $backend_station['coordinate']['type'],
                'x' => $backend_station['coordinate']['x'],
                'y' => $backend_station['coordinate']['y'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $backend_result
     *
     * @return array<array{sections: array<mixed>}>
     */
    protected function getConnections(array $backend_result): array {
        $new_connections = [];
        foreach ($backend_result['connections'] as $connection) {
            $new_sections = [];
            foreach ($connection['sections'] as $section) {
                $new_section = [];
                $new_section['departure'] = $this->convertStop($section['departure']);
                $new_section['arrival'] = $this->convertStop($section['arrival']);
                $new_section['passList'] = [];
                foreach ($section['journey']['passList'] as $stop) {
                    $new_section['passList'][] = $this->convertStop($stop);
                }
                $new_sections[] = $new_section;
            }
            $new_connection = ['sections' => $new_sections];
            $new_connections[] = $new_connection;
        }
        return $new_connections;
    }

    /**
     * @param array<string, mixed> $backend_stop
     *
     * @return array{stationId: string, arrival: ?string, departure: ?string, delay: int|float, platform: ?string}
     */
    protected function convertStop(array $backend_stop): array {
        return [
            'stationId' => $backend_stop['station']['id'],
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
