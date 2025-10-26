<?php

namespace App\Http\Middleware;

use App\Enums\AppointmentStatus;
use App\Enums\Gender;
use App\Enums\PatientStatus;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Inertia\Middleware;

use function auth;
use function config;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request) : ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request) : array
    {
        // collect params we want on every page
        $params = collect([
            'auth'   => [
                'user' => new UserResource(auth()->user()),
            ],
            'flash'  => [
                'message' => fn() => $request->session()
                    ->get('message'),
                'type'    => fn() => $request->session()
                    ->get('type'),
            ],
            'header' => config('app.name'),
            ...parent::share($request),
            //
        ]);

        // now do some conditionals
        if ($request->routeIs('patients.*')) {
            $params->put('attributes', [
                'patient'     => [
                    'statuses' => PatientStatus::cases(),
                    'genders'  => Gender::cases()
                ],
                'appointment' => [
                    'statuses' => AppointmentStatus::cases(),
                ]
            ]);
        }

        return $params->toArray();

    }
}
