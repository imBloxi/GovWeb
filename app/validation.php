<?php
declare(strict_types=1);

// Names used in error messages when the column name doesn't read well.
const FIELD_LABELS = [
    'dob' => 'Date of birth',
    'civilian_id' => 'Person',
    'owner_id' => 'Owner',
    'court_file_id' => 'Case file',
    'license_type' => 'License type',
    'issued_on' => 'Issue date',
    'expires_on' => 'Expiry date',
    'offense_date' => 'Date of offense',
    'registered_on' => 'Registration date',
    'filed_on' => 'Filing date',
];

/**
 * Validate input against simple pipe-separated rules.
 *
 *   [$data, $errors] = validate([
 *       'first_name' => 'required|max:60',
 *       'email'      => 'email|max:120',
 *       'gender'     => 'required|in:male,female,other',
 *   ]);
 *
 * Returns the trimmed values (empty optional fields become null) and
 * an array of field => error message.
 */
function validate(array $rules, ?array $source = null): array
{
    $source ??= $_POST;
    $data = [];
    $errors = [];

    foreach ($rules as $field => $ruleString) {
        $raw = $source[$field] ?? '';
        $value = is_string($raw) ? trim($raw) : '';
        $label = FIELD_LABELS[$field] ?? ucfirst(str_replace('_', ' ', $field));
        $rulesList = explode('|', $ruleString);

        if ($value === '') {
            if (in_array('required', $rulesList, true)) {
                $errors[$field] = "$label is required.";
            }
            $data[$field] = null;
            continue;
        }

        foreach ($rulesList as $rule) {
            [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
            $error = match ($name) {
                'max' => mb_strlen($value) > (int) $arg ? "$label must be at most $arg characters." : null,
                'min' => mb_strlen($value) < (int) $arg ? "$label must be at least $arg characters." : null,
                'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "$label must be a valid email address.",
                'int' => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "$label must be a whole number.",
                'date' => is_valid_date($value) ? null : "$label must be a valid date.",
                'past' => strtotime($value) <= time() ? null : "$label can't be in the future.",
                'after' => (isset($data[$arg]) && $value < $data[$arg])
                    ? "$label must be on or after the " . mb_strtolower(FIELD_LABELS[$arg] ?? str_replace('_', ' ', $arg)) . '.' : null,
                'in' => in_array($value, explode(',', (string) $arg), true) ? null : "Choose a valid " . mb_strtolower($label) . ".",
                'phone' => preg_match('/^[0-9 +()\-]{6,30}$/', $value) ? null : "$label can only contain digits, spaces and + ( ) -.",
                default => null,
            };
            if ($error) {
                $errors[$field] = $error;
                break;
            }
        }
        $data[$field] = $value;
    }

    return [$data, $errors];
}

function is_valid_date(string $value): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $d && $d->format('Y-m-d') === $value;
}
