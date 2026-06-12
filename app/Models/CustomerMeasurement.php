<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMeasurement extends Model
{
    public const TYPES = ['women', 'men', 'kid'];

    /** @var list<string> Additional measurement keys accepted at the API root or inside extra_measurements. */
    public const EXTRA_FIELDS = [
        'blouse_length',
        'shoulder',
        'sleeve_length',
        'sleeve_loose',
        'arm_hole',
        'dot_point',
        'front_neck',
        'back_neck',
        'top_length',
        'half_length',
        'slit',
        'hip',
        'seat',
        'bottom_length',
        'leg_loose',
        'thigh',
        'knees',
    ];

    /** @return array<string, array<int, string>> */
    public static function apiValidationRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        $measurementValue = ['nullable', 'string', 'max:50'];

        $rules = [
            'name' => [$required, 'string', 'max:255'],
            'measurement_type' => ['nullable', 'in:women,men,kid'],
            'height_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'height' => $measurementValue,
            'chest_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'chest' => $measurementValue,
            'waist_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'waist' => $measurementValue,
            'extra_measurements' => ['nullable', 'array'],
        ];

        foreach (self::EXTRA_FIELDS as $field) {
            $rules[$field] = $measurementValue;
            $rules["extra_measurements.{$field}"] = $measurementValue;
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeApiPayload(array $data, ?self $existing = null): array
    {
        $extra = $existing ? ($existing->extra_measurements ?? []) : [];

        if (isset($data['extra_measurements']) && is_array($data['extra_measurements'])) {
            foreach (self::EXTRA_FIELDS as $field) {
                if (array_key_exists($field, $data['extra_measurements'])) {
                    self::applyExtraField($extra, $field, $data['extra_measurements'][$field]);
                }
            }
        }

        foreach (self::EXTRA_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                self::applyExtraField($extra, $field, $data[$field]);
            }
        }

        $payload = [];

        if (array_key_exists('name', $data)) {
            $payload['name'] = $data['name'];
        }

        if (array_key_exists('measurement_type', $data)) {
            $payload['measurement_type'] = $data['measurement_type'];
        }

        if (array_key_exists('height_cm', $data)) {
            $payload['height_cm'] = $data['height_cm'];
        } elseif (array_key_exists('height', $data)) {
            if ($data['height'] !== '' && is_numeric($data['height'])) {
                $payload['height_cm'] = (int) $data['height'];
            } elseif ($data['height'] !== '') {
                self::applyExtraField($extra, 'height', $data['height']);
            } elseif ($existing) {
                unset($extra['height']);
                $payload['height_cm'] = null;
            }
        }

        if (array_key_exists('chest_cm', $data)) {
            $payload['chest_cm'] = $data['chest_cm'];
        } elseif (array_key_exists('chest', $data)) {
            if ($data['chest'] !== '' && is_numeric($data['chest'])) {
                $payload['chest_cm'] = (int) $data['chest'];
            } elseif ($data['chest'] !== '') {
                self::applyExtraField($extra, 'chest', $data['chest']);
                if ($existing) {
                    $payload['chest_cm'] = null;
                }
            } elseif ($existing) {
                unset($extra['chest']);
                $payload['chest_cm'] = null;
            }
        }

        if (array_key_exists('waist_cm', $data)) {
            $payload['waist_cm'] = $data['waist_cm'];
        } elseif (array_key_exists('waist', $data)) {
            if ($data['waist'] !== '' && is_numeric($data['waist'])) {
                $payload['waist_cm'] = (int) $data['waist'];
            } elseif ($data['waist'] !== '') {
                self::applyExtraField($extra, 'waist', $data['waist']);
                if ($existing) {
                    $payload['waist_cm'] = null;
                }
            } elseif ($existing) {
                unset($extra['waist']);
                $payload['waist_cm'] = null;
            }
        }

        if (self::extraInputPresent($data)) {
            $payload['extra_measurements'] = $extra !== [] ? $extra : null;
        } elseif (! $existing && $extra !== []) {
            $payload['extra_measurements'] = $extra;
        }

        return $payload;
    }

    /** @param array<string, string|null> $extra */
    protected static function applyExtraField(array &$extra, string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            unset($extra[$field]);

            return;
        }

        $extra[$field] = (string) $value;
    }

    /** @param array<string, mixed> $data */
    protected static function extraInputPresent(array $data): bool
    {
        if (isset($data['extra_measurements']) && is_array($data['extra_measurements'])) {
            return true;
        }

        foreach (self::EXTRA_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, string|null> */
    public function apiMeasurementFields(): array
    {
        $extra = $this->extra_measurements ?? [];
        $fields = [];

        foreach (self::EXTRA_FIELDS as $field) {
            $fields[$field] = isset($extra[$field]) ? (string) $extra[$field] : null;
        }

        $fields['chest'] = $this->chest_cm !== null
            ? (string) $this->chest_cm
            : (isset($extra['chest']) ? (string) $extra['chest'] : null);

        $fields['waist'] = $this->waist_cm !== null
            ? (string) $this->waist_cm
            : (isset($extra['waist']) ? (string) $extra['waist'] : null);

        return $fields;
    }

    protected $fillable = [
        'customer_id',
        'name',
        'measurement_type',
        'height_cm',
        'chest_cm',
        'waist_cm',
        'extra_measurements',
    ];

    protected function casts(): array
    {
        return [
            'height_cm' => 'integer',
            'chest_cm' => 'integer',
            'waist_cm' => 'integer',
            'extra_measurements' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
