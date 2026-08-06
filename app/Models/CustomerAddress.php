<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'label',
        'name',
        'mobile_number',
        'country',
        'house_no',
        'road_area',
        'address_line',
        'city',
        'state',
        'pincode',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function fullAddress(): string
    {
        $fromParts = trim(implode(', ', array_filter([
            $this->house_no,
            $this->road_area,
        ], fn ($v) => filled($v))));

        $street = $fromParts !== '' ? $fromParts : (string) ($this->address_line ?: '');

        return trim(implode(', ', array_filter([
            $street,
            $this->city,
            $this->state,
            $this->pincode,
            $this->country,
        ], fn ($v) => filled($v))));
    }

    public function line(): string
    {
        $fromParts = trim(implode(', ', array_filter([
            $this->house_no,
            $this->road_area,
        ], fn ($v) => filled($v))));

        return $fromParts !== '' ? $fromParts : (string) ($this->address_line ?: '');
    }
}
