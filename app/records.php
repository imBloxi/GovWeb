<?php
declare(strict_types=1);

// Shared rules for creating register entries, used by staff forms and by
// approving civilian applications.

const LICENSE_TYPES = ['car' => 'Car', 'motorcycle' => 'Motorcycle', 'scooter' => 'Scooter', 'truck' => 'Truck'];
const LICENSE_PREFIX = ['car' => 'CAR', 'motorcycle' => 'MOT', 'scooter' => 'SCO', 'truck' => 'TRK'];
const LICENSE_YEARS = 10;

const APPLICATION_TYPES = [
    'license'  => 'Driving license',
    'business' => 'Business registration',
    'vehicle'  => 'Vehicle registration',
];

/** Run $make until it returns a value not already in $table.$column. */
function unique_value(string $table, string $column, callable $make): string
{
    do {
        $value = $make();
    } while (db_value("SELECT 1 FROM `$table` WHERE `$column` = ?", [$value]));
    return $value;
}

function new_license_number(string $type): string
{
    return unique_value('licenses', 'license_number', fn() => LICENSE_PREFIX[$type] . '-' . random_int(100000, 999999));
}

/** Number plates look like "AB-1234". Letters that are easy to misread are left out. */
function new_plate(): string
{
    $letters = 'ABCDEFGHJKLMNPRSTUVZ';
    return unique_value('vehicles', 'plate', fn() =>
        $letters[random_int(0, 19)] . $letters[random_int(0, 19)] . '-' . random_int(1000, 9999));
}

function normalize_plate(string $plate): string
{
    return strtoupper(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $plate) ?? '', '-'));
}

function create_license(int $civilianId, string $type, ?string $issuedOn = null): array
{
    $issuedOn ??= date('Y-m-d');
    $data = [
        'civilian_id' => $civilianId,
        'license_type' => $type,
        'license_number' => new_license_number($type),
        'issued_on' => $issuedOn,
        'expires_on' => date('Y-m-d', strtotime($issuedOn . ' +' . LICENSE_YEARS . ' years')),
        'status' => 'active',
    ];
    $data['id'] = db_insert('licenses', $data);
    audit('create', 'license', $data['id'], "Issued license {$data['license_number']}");
    return $data;
}

/** Applications store their form fields as JSON. */
function application_details(array $application): array
{
    return json_decode($application['details'], true) ?: [];
}

/** Human-readable lines describing what an application asks for. */
function application_summary(array $application): array
{
    $d = application_details($application);
    return match ($application['type']) {
        'license' => array_filter([
            'License' => LICENSE_TYPES[$d['license_type'] ?? ''] ?? '',
            'Note' => $d['note'] ?? '',
        ]),
        'business' => array_filter([
            'Name' => $d['name'] ?? '',
            'Type' => $d['business_type'] ?? '',
            'Address' => $d['address'] ?? '',
        ]),
        'vehicle' => array_filter([
            'Vehicle' => trim(($d['year'] ?? '') . ' ' . ($d['make'] ?? '') . ' ' . ($d['model'] ?? '')),
            'Colour' => $d['color'] ?? '',
            'VIN' => $d['vin'] ?? '',
        ]),
        default => [],
    };
}
