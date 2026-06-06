<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMeasurement extends Model
{
    public const TYPES = ['women', 'men', 'kid'];

    public const EXTRA_FIELDS = [
        'blouse_length',
        'shoulder',
        'arm_hole',
        'dot_point',
        'sleeve_length',
        'sleeve_loose',
        'front_neck',
        'back_neck',
        'hip',
        'seat',
        'bottom_length',
        'leg_loose',
        'thigh',
        'knees',
        'top_length',
        'half_length',
        'slit',
    ];

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
