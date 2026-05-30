<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vendors', 'service_types')) {
            return;
        }

        DB::table('vendors')
            ->whereNotNull('service_types')
            ->orderBy('id')
            ->each(function (object $vendor): void {
                $raw = $vendor->service_types;

                if (! is_string($raw) || $raw === '') {
                    return;
                }

                $decoded = json_decode($raw, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return;
                }

                $string = is_array($decoded)
                    ? implode(', ', array_filter(array_map('strval', $decoded)))
                    : trim((string) $decoded);

                DB::table('vendors')
                    ->where('id', $vendor->id)
                    ->update(['service_types' => $string !== '' ? $string : null]);
            });

        DB::statement('ALTER TABLE vendors MODIFY service_types VARCHAR(500) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('vendors', 'service_types')) {
            return;
        }

        DB::statement('ALTER TABLE vendors MODIFY service_types JSON NULL');
    }
};
