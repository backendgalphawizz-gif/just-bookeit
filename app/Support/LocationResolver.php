<?php

namespace App\Support;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LocationResolver
{
    public const OTHER = 'other';

    /** @return array{country: string, state: string, city: string} */
    public static function resolve(array $input): array
    {
        $country = self::resolveCountry($input);
        $state = self::resolveState($country, $input);
        $city = self::resolveCity($state, $input);

        return [
            'country' => $country->name,
            'state' => $state->name,
            'city' => $city->name,
        ];
    }

    /**
     * @return array{
     *     country_id: string,
     *     state_id: string,
     *     city_id: string,
     *     country_other: string,
     *     state_other: string,
     *     city_other: string
     * }
     */
    public static function formDefaultsFromNames(?string $country, ?string $state, ?string $city): array
    {
        $defaults = [
            'country_id' => '',
            'state_id' => '',
            'city_id' => '',
            'country_other' => '',
            'state_other' => '',
            'city_other' => '',
        ];

        if (filled($country)) {
            $countryModel = self::findCountryByName($country);

            if ($countryModel) {
                $defaults['country_id'] = (string) $countryModel->id;
            } else {
                $defaults['country_id'] = self::OTHER;
                $defaults['country_other'] = trim($country);
            }
        }

        if (filled($state)) {
            if ($defaults['country_id'] !== '' && $defaults['country_id'] !== self::OTHER) {
                $stateModel = self::findStateByName((int) $defaults['country_id'], $state);

                if ($stateModel) {
                    $defaults['state_id'] = (string) $stateModel->id;
                } else {
                    $defaults['state_id'] = self::OTHER;
                    $defaults['state_other'] = trim($state);
                }
            } else {
                $defaults['state_id'] = self::OTHER;
                $defaults['state_other'] = trim($state);
            }
        }

        if (filled($city)) {
            if ($defaults['state_id'] !== '' && $defaults['state_id'] !== self::OTHER) {
                $cityModel = self::findCityByName((int) $defaults['state_id'], $city);

                if ($cityModel) {
                    $defaults['city_id'] = (string) $cityModel->id;
                } else {
                    $defaults['city_id'] = self::OTHER;
                    $defaults['city_other'] = trim($city);
                }
            } else {
                $defaults['city_id'] = self::OTHER;
                $defaults['city_other'] = trim($city);
            }
        }

        return $defaults;
    }

    /** @return list<array<string, mixed>> */
    public static function catalog(): array
    {
        return Country::query()
            ->where('is_active', true)
            ->with([
                'states' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->with([
                        'cities' => fn ($cityQuery) => $cityQuery
                            ->where('is_active', true)
                            ->orderBy('name'),
                    ]),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Country $country) => [
                'id' => $country->id,
                'name' => $country->name,
                'states' => $country->states->map(fn (State $state) => [
                    'id' => $state->id,
                    'name' => $state->name,
                    'cities' => $state->cities->map(fn (City $city) => [
                        'id' => $city->id,
                        'name' => $city->name,
                    ])->values()->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    protected static function findCountryByName(string $name): ?Country
    {
        return self::findByName(Country::query(), $name);
    }

    protected static function findStateByName(int $countryId, string $name): ?State
    {
        return self::findByName(State::query()->where('country_id', $countryId), $name);
    }

    protected static function findCityByName(int $stateId, string $name): ?City
    {
        return self::findByName(City::query()->where('state_id', $stateId), $name);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query */
    protected static function findByName($query, string $name): ?object
    {
        $trimmed = trim($name);
        $normalized = Str::title($trimmed);

        return $query
            ->where(function ($builder) use ($trimmed, $normalized) {
                $builder->where('name', $trimmed)
                    ->orWhere('name', $normalized)
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)]);
            })
            ->first();
    }

    protected static function resolveCountry(array $input): Country
    {
        $countryId = $input['country_id'] ?? null;

        if ($countryId && $countryId !== self::OTHER) {
            return Country::query()->findOrFail((int) $countryId);
        }

        $name = self::cleanName($input['country_other'] ?? $input['country'] ?? null, 'country');

        return Country::query()->firstOrCreate(
            ['name' => $name],
            ['is_active' => true]
        );
    }

    protected static function resolveState(Country $country, array $input): State
    {
        $stateId = $input['state_id'] ?? null;

        if ($stateId && $stateId !== self::OTHER) {
            $state = State::query()->findOrFail((int) $stateId);

            if ($state->country_id !== $country->id) {
                throw ValidationException::withMessages([
                    'state_id' => ['Selected state does not belong to the chosen country.'],
                ]);
            }

            return $state;
        }

        $name = self::cleanName($input['state_other'] ?? $input['state'] ?? null, 'state');

        return State::query()->firstOrCreate(
            ['country_id' => $country->id, 'name' => $name],
            ['is_active' => true]
        );
    }

    protected static function resolveCity(State $state, array $input): City
    {
        $cityId = $input['city_id'] ?? null;

        if ($cityId && $cityId !== self::OTHER) {
            $city = City::query()->findOrFail((int) $cityId);

            if ($city->state_id !== $state->id) {
                throw ValidationException::withMessages([
                    'city_id' => ['Selected city does not belong to the chosen state.'],
                ]);
            }

            return $city;
        }

        $name = self::cleanName($input['city_other'] ?? $input['city'] ?? null, 'city');

        return City::query()->firstOrCreate(
            ['state_id' => $state->id, 'name' => $name],
            ['is_active' => true]
        );
    }

    protected static function cleanName(mixed $value, string $field): string
    {
        $name = trim((string) $value);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        if ($name === '' || ! preg_match(AdminValidationRules::REGEX_CITY, $name)) {
            throw ValidationException::withMessages([
                "{$field}_other" => ['Please enter a valid '.str_replace('_', ' ', $field).' name.'],
            ]);
        }

        return Str::title($name);
    }
}
