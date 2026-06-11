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
    public static function normalizeApiPayload(array $data): array
    {
        $extra = collect($data['extra_measurements'] ?? [])
            ->only(self::EXTRA_FIELDS)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        foreach (self::EXTRA_FIELDS as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                $extra[$field] = $data[$field];
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
        } elseif (array_key_exists('height', $data) && $data['height'] !== '' && is_numeric($data['height'])) {
            $payload['height_cm'] = (int) $data['height'];
        } elseif (array_key_exists('height', $data) && $data['height'] !== '') {
            $extra['height'] = $data['height'];
        }

        if (array_key_exists('chest_cm', $data)) {
            $payload['chest_cm'] = $data['chest_cm'];
        } elseif (array_key_exists('chest', $data) && $data['chest'] !== '' && is_numeric($data['chest'])) {
            $payload['chest_cm'] = (int) $data['chest'];
        } elseif (array_key_exists('chest', $data) && $data['chest'] !== '') {
            $extra['chest'] = $data['chest'];
        }

        if (array_key_exists('waist_cm', $data)) {
            $payload['waist_cm'] = $data['waist_cm'];
        } elseif (array_key_exists('waist', $data) && $data['waist'] !== '' && is_numeric($data['waist'])) {
            $payload['waist_cm'] = (int) $data['waist'];
        } elseif (array_key_exists('waist', $data) && $data['waist'] !== '') {
            $extra['waist'] = $data['waist'];
        }

        if ($extra !== []) {
            $payload['extra_measurements'] = $extra;
        } elseif (array_key_exists('extra_measurements', $data)) {
            $payload['extra_measurements'] = null;
        }

        return $payload;
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
