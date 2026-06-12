<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Support\Api\CustomerApiPresenter;
use App\Support\LocationResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends ApiController
{
    public function countries(): JsonResponse
    {
        $countries = Country::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return $this->success([
            'items' => $countries->map(fn (Country $country) => [
                'id' => $country->id,
                'name' => $country->name,
            ])->values()->all(),
            'other_value' => LocationResolver::OTHER,
        ]);
    }

    public function states(Request $request): JsonResponse
    {
        $request->validate([
            'country_id' => ['required', 'integer', 'exists:countries,id'],
        ]);

        $states = State::query()
            ->where('country_id', $request->integer('country_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'country_id']);

        return $this->success([
            'items' => $states->map(fn (State $state) => [
                'id' => $state->id,
                'name' => $state->name,
                'country_id' => $state->country_id,
            ])->values()->all(),
            'other_value' => LocationResolver::OTHER,
        ]);
    }

    public function cities(Request $request): JsonResponse
    {
        $request->validate([
            'state_id' => ['required', 'integer', 'exists:states,id'],
        ]);

        $cities = City::query()
            ->where('state_id', $request->integer('state_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'state_id']);

        return $this->success([
            'items' => $cities->map(fn (City $city) => [
                'id' => $city->id,
                'name' => $city->name,
                'state_id' => $city->state_id,
            ])->values()->all(),
            'other_value' => LocationResolver::OTHER,
        ]);
    }

    public function catalog(): JsonResponse
    {
        return $this->success([
            'locations' => LocationResolver::catalog(),
            'other_value' => LocationResolver::OTHER,
        ]);
    }
}
